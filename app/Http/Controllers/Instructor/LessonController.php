<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Lesson;
use App\Models\LessonTranslation;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\ChunkedVideoUpload;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LessonController extends Controller
{
    use AuthorizesCourse;

    public function __construct(private ChunkedVideoUpload $uploads) {}

    public function store(Request $request, Module $module)
    {
        $course = $module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type'  => ['required', Rule::in(Lesson::TYPES)],
        ]);

        $lesson = DB::transaction(function () use ($module, $course, $data) {
            $lesson = Lesson::create([
                'module_id' => $module->id,
                'order'     => (int) $module->lessons()->max('order') + 1,
                'type'      => $data['type'],
            ]);
            LessonTranslation::create(['lesson_id' => $lesson->id, 'locale' => $course->language, 'title' => $data['title']]);
            if ($data['type'] === 'quiz') {
                Quiz::create(['lesson_id' => $lesson->id, 'passing_score' => 70]);
            }
            return $lesson;
        });

        return redirect()->route('instructor.lessons.edit', $lesson)->with('success', __('lms.lesson_created'));
    }

    public function edit(Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);

        $lesson->load(['translations', 'resources', 'quiz.questions.options', 'module.translations']);
        $course->load(['translations', 'modules.lessons.translations']);
        $locales = CourseController::CONTENT_LOCALES;

        return view('instructor.lessons.edit', compact('lesson', 'course', 'locales'));
    }

    public function update(Request $request, Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $data = $request->validate([
            'type'                       => ['required', Rule::in(Lesson::TYPES)],
            'duration_minutes'           => ['required', 'integer', 'min:0', 'max:1440'],
            'is_free_preview'            => ['nullable', 'boolean'],
            'is_downloadable'            => ['nullable', 'boolean'],
            'translations'               => ['required', 'array'],
            'translations.*.title'       => ['nullable', 'string', 'max:255'],
            'translations.*.content'     => ['nullable', 'string', 'max:500000'],
            'video_source'               => ['nullable', Rule::in([Lesson::VIDEO_UPLOAD, Lesson::VIDEO_YOUTUBE, Lesson::VIDEO_VIMEO, Lesson::VIDEO_URL])],
            'youtube_url'                => ['nullable', 'string', 'max:500'],
            'vimeo_url'                  => ['nullable', 'string', 'max:500'],
            'external_url'               => ['nullable', 'url', 'max:500'],
            'assignment_max_score'       => ['nullable', 'integer', 'min:1', 'max:1000'],
            'assignment_pass_score'      => ['nullable', 'integer', 'min:0', 'lte:assignment_max_score'],
        ]);
        $request->validate(
            ["translations.{$course->language}.title" => ['required', 'string', 'max:255']],
            [],
            ["translations.{$course->language}.title" => __('lms.lesson_title')]
        );

        $video = $this->resolveVideo($request, $lesson, $data);

        DB::transaction(function () use ($lesson, $data, $video) {
            $lesson->update([
                'type'                  => $data['type'],
                'duration_minutes'      => $data['duration_minutes'],
                'is_free_preview'       => (bool) ($data['is_free_preview'] ?? false),
                'is_downloadable'       => (bool) ($data['is_downloadable'] ?? false),
                'assignment_max_score'  => $data['assignment_max_score'] ?? $lesson->assignment_max_score,
                'assignment_pass_score' => $data['assignment_pass_score'] ?? $lesson->assignment_pass_score,
                'content'               => null,
            ] + $video);

            foreach (CourseController::CONTENT_LOCALES as $locale) {
                $trans = $data['translations'][$locale] ?? [];
                if (blank($trans['title'] ?? null)) {
                    LessonTranslation::where('lesson_id', $lesson->id)->where('locale', $locale)->delete();
                    continue;
                }
                LessonTranslation::updateOrCreate(
                    ['lesson_id' => $lesson->id, 'locale' => $locale],
                    ['title' => $trans['title'], 'content' => HtmlSanitizer::clean($trans['content'] ?? '')]
                );
            }

            if ($data['type'] === 'quiz' && ! $lesson->quiz()->exists()) {
                Quiz::create(['lesson_id' => $lesson->id, 'passing_score' => 70]);
            }

            // Bumps updated_at even when only translations changed: offline copies compare it.
            $lesson->touch();
        });

        $lesson->module->course->recalculateDuration();

        return redirect()->route('instructor.lessons.edit', $lesson)->with('success', __('lms.lesson_saved'));
    }

    public function destroy(Request $request, Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $this->uploads->deleteVideo($lesson);
        foreach ($lesson->resources as $resource) {
            Storage::disk('local')->delete($resource->path);
        }
        $lesson->delete();
        $course->recalculateDuration();

        // Progress percentages depend on the lesson count.
        foreach ($course->enrollments()->get() as $enrollment) {
            $enrollment->recalculateProgress();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('lms.lesson_deleted')]);
        }
        return redirect()->route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum'])
            ->with('success', __('lms.lesson_deleted'));
    }

    /**
     * Saves lesson order after drag & drop, including moves between modules:
     * { modules: [ { id: 4, lessons: [12, 9] }, { id: 5, lessons: [10] } ] }
     */
    public function reorder(Request $request, \App\Models\Course $course)
    {
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $data = $request->validate([
            'modules'             => ['required', 'array'],
            'modules.*.id'        => ['required', 'integer'],
            'modules.*.lessons'   => ['present', 'array'],
            'modules.*.lessons.*' => ['integer'],
        ]);

        $moduleIds = $course->modules()->pluck('id')->all();
        $lessonIds = Lesson::whereIn('module_id', $moduleIds)->pluck('id')->all();

        foreach ($data['modules'] as $entry) {
            abort_unless(in_array($entry['id'], $moduleIds, true), 422);
            abort_if(array_diff($entry['lessons'], $lessonIds), 422);
        }

        DB::transaction(function () use ($data) {
            foreach ($data['modules'] as $entry) {
                foreach ($entry['lessons'] as $position => $lessonId) {
                    Lesson::whereKey($lessonId)->update(['module_id' => $entry['id'], 'order' => $position + 1]);
                }
            }
        });

        return response()->json(['message' => __('lms.order_saved')]);
    }

    // ─── Chunked video upload ─────────────────────────────────────────────────

    /** Which chunks of this upload the server already has (for resuming). */
    public function uploadStatus(Request $request, Lesson $lesson)
    {
        $this->authorizeCourse($lesson->module->course);
        $uploadId = $request->validate(['upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/']])['upload_id'];

        return response()->json(['received' => $this->uploads->receivedChunks($request->user()->id, $uploadId)]);
    }

    public function uploadChunk(Request $request, Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $maxBytes = config('lms.video.max_size_mb') * 1024 * 1024;
        $chunkKb  = (config('lms.video.chunk_size_mb') + 1) * 1024;

        $data = $request->validate([
            'upload_id'        => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'index'            => ['required', 'integer', 'min:0'],
            'total'            => ['required', 'integer', 'min:1', 'max:10000'],
            'filename'         => ['required', 'string', 'max:255'],
            'size'             => ['required', 'integer', 'min:1', 'max:' . $maxBytes],
            'duration_seconds' => ['nullable', 'numeric', 'min:0'],
            'chunk'            => ['required', 'file', 'max:' . $chunkKb],
        ], [
            'size.max' => __('lms.upload_too_large', ['max' => config('lms.video.max_size_mb')]),
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        abort_unless(in_array($ext, config('lms.video.extensions'), true), 422, __('lms.upload_bad_extension'));
        abort_if($data['index'] >= $data['total'], 422);

        $userId = $request->user()->id;
        $this->uploads->storeChunk($userId, $data['upload_id'], $data['index'], $request->file('chunk'));

        if (! $this->uploads->isComplete($userId, $data['upload_id'], $data['total'])) {
            return response()->json(['done' => false, 'received' => $data['index']]);
        }

        $this->uploads->finalize($lesson, $userId, $data['upload_id'], $data['total'], $data['filename']);

        // Use the real video length for the lesson duration when the instructor has not set one.
        if (! empty($data['duration_seconds']) && ! $lesson->duration_minutes) {
            $lesson->update(['duration_minutes' => max(1, (int) ceil($data['duration_seconds'] / 60))]);
            $course->recalculateDuration();
        }
        $lesson->update(['type' => 'video']);

        return response()->json([
            'done'    => true,
            'message' => __('lms.video_uploaded'),
            'video'   => [
                'name' => $lesson->video_original_name,
                'size' => \App\Support\Format::bytes($lesson->video_size),
                'url'  => route('media.lesson.video', $lesson),
            ],
            'duration_minutes' => $lesson->duration_minutes,
        ]);
    }

    public function deleteVideo(Request $request, Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $this->uploads->deleteVideo($lesson);
        $lesson->update([
            'video_source' => null, 'video_path' => null, 'video_original_name' => null,
            'video_size' => null, 'video_mime' => null,
        ]);

        return redirect()->route('instructor.lessons.edit', $lesson)->with('success', __('lms.video_deleted'));
    }

    /** Attributes for the chosen video source; uploads are handled by uploadChunk(). */
    private function resolveVideo(Request $request, Lesson $lesson, array $data): array
    {
        if ($data['type'] !== 'video') {
            return [];
        }

        return match ($data['video_source'] ?? null) {
            Lesson::VIDEO_YOUTUBE => $this->youtubeAttributes($lesson, (string) ($data['youtube_url'] ?? '')),
            Lesson::VIDEO_VIMEO   => $this->vimeoAttributes($lesson, (string) ($data['vimeo_url'] ?? '')),
            Lesson::VIDEO_URL     => $this->switchingFromUpload($lesson) + [
                'video_source' => Lesson::VIDEO_URL, 'video_url' => $data['external_url'] ?? null, 'youtube_id' => null,
            ],
            // Upload: keep the file already attached (or none yet).
            default => $lesson->video_path ? [] : ['video_source' => Lesson::VIDEO_UPLOAD],
        };
    }

    private function youtubeAttributes(Lesson $lesson, string $url): array
    {
        $id = Lesson::parseYoutubeId($url);
        if (! $id) {
            throw \Illuminate\Validation\ValidationException::withMessages(['youtube_url' => __('lms.youtube_invalid')]);
        }
        return $this->switchingFromUpload($lesson) + [
            'video_source' => Lesson::VIDEO_YOUTUBE,
            'youtube_id'   => $id,
            'video_url'    => 'https://www.youtube.com/watch?v=' . $id,
        ];
    }

    private function vimeoAttributes(Lesson $lesson, string $url): array
    {
        if (! Lesson::parseVimeoId($url)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['vimeo_url' => __('lms.vimeo_invalid')]);
        }
        return $this->switchingFromUpload($lesson) + [
            'video_source' => Lesson::VIDEO_VIMEO, 'video_url' => $url, 'youtube_id' => null,
        ];
    }

    /** Replacing an uploaded file by a link frees the stored file. */
    private function switchingFromUpload(Lesson $lesson): array
    {
        if (! $lesson->video_path) {
            return [];
        }
        $this->uploads->deleteVideo($lesson);
        return ['video_path' => null, 'video_original_name' => null, 'video_size' => null, 'video_mime' => null];
    }
}
