<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseTranslation;
use App\Models\User;
use App\Notifications\CourseSubmittedForReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    use AuthorizesCourse;

    /** Content languages an instructor can author in. */
    public const CONTENT_LOCALES = ['fr', 'en', 'es', 'pt', 'zh', 'ar'];

    public function index(Request $request)
    {
        $courses = $request->user()->courses()
            ->with(['translations', 'category.translations'])
            ->withCount(['enrollments', 'reviews', 'modules'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(15);

        return view('instructor.courses.index', compact('courses'));
    }

    public function create()
    {
        $categories = Category::active()->with('translations')->orderBy('order')->get();
        return view('instructor.courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'language'       => ['required', 'in:' . implode(',', self::CONTENT_LOCALES)],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'max:20000'],
            'category_id'    => ['required', 'exists:categories,id'],
            'level'          => ['required', 'in:beginner,intermediate,advanced'],
            'duration_hours' => ['required', 'numeric', 'min:0.5', 'max:2000'],
            'price'          => ['nullable', 'numeric', 'min:0'],
            'thumbnail'      => ['nullable', 'image', 'max:4096', 'mimes:jpeg,png,jpg,webp'],
        ]);

        $course = Course::create([
            'instructor_id'  => $request->user()->id,
            'category_id'    => $data['category_id'],
            'level'          => $data['level'],
            'language'       => $data['language'],
            'duration_hours' => $data['duration_hours'],
            'price'          => $data['price'] ?? 0,
            'status'         => 'draft',
            'slug'           => Str::slug($data['title']) . '-' . Str::lower(Str::random(5)),
            'thumbnail'      => $this->storeThumbnail($request),
        ]);

        CourseTranslation::create([
            'course_id'   => $course->id,
            'locale'      => $data['language'],
            'title'       => $data['title'],
            'description' => $data['description'],
        ]);

        return redirect()->route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum'])
            ->with('success', __('lms.course_created_next_step'));
    }

    public function edit(Request $request, Course $course)
    {
        $this->authorizeCourse($course);

        $categories = Category::active()->with('translations')->orderBy('order')->get();
        $course->load([
            'translations',
            'modules.translations',
            'modules.lessons.translations',
            'modules.lessons.quiz.questions',
            'modules.lessons.resources',
            'modules.exam.questions',
            'finalExam.questions',
            'books',
        ])->loadCount('enrollments')->loadAvg('reviews', 'rating');

        $locales   = self::CONTENT_LOCALES;
        $checklist = $this->publishChecklist($course);
        $tab       = $request->query('tab', 'info');

        return view('instructor.courses.edit', compact('course', 'categories', 'locales', 'checklist', 'tab'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $data = $request->validate([
            'category_id'    => ['required', 'exists:categories,id'],
            'level'          => ['required', 'in:beginner,intermediate,advanced'],
            'language'       => ['required', 'in:' . implode(',', self::CONTENT_LOCALES)],
            'duration_hours' => ['required', 'numeric', 'min:0.5', 'max:2000'],
            'is_sequential'  => ['nullable', 'boolean'],
            'price'          => ['nullable', 'numeric', 'min:0'],
            'thumbnail'      => ['nullable', 'image', 'max:4096', 'mimes:jpeg,png,jpg,webp'],
            'translations'                    => ['required', 'array'],
            'translations.*.title'            => ['nullable', 'string', 'max:255'],
            'translations.*.description'      => ['nullable', 'string', 'max:20000'],
            'translations.*.what_you_learn'   => ['nullable', 'string', 'max:5000'],
            'translations.*.requirements'     => ['nullable', 'string', 'max:5000'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:300'],
        ]);

        // The course's main language must always have a title.
        $request->validate([
            "translations.{$data['language']}.title"       => ['required', 'string', 'max:255'],
            "translations.{$data['language']}.description" => ['required', 'string'],
        ], [], [
            "translations.{$data['language']}.title"       => __('lms.title'),
            "translations.{$data['language']}.description" => __('lms.description'),
        ]);

        $thumbnail = $course->thumbnail;
        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail && ! str_starts_with($course->thumbnail, 'http')) {
                Storage::disk('public')->delete('thumbnails/' . $course->thumbnail);
            }
            $thumbnail = $this->storeThumbnail($request);
        }

        $course->update([
            'category_id'    => $data['category_id'],
            'level'          => $data['level'],
            'language'       => $data['language'],
            'duration_hours' => $data['duration_hours'],
            'is_sequential'  => $request->boolean('is_sequential'),
            'price'          => $data['price'] ?? $course->price,
            'thumbnail'      => $thumbnail,
        ]);

        foreach (self::CONTENT_LOCALES as $locale) {
            $trans = $data['translations'][$locale] ?? [];
            if (blank($trans['title'] ?? null)) {
                // An emptied secondary language is removed rather than kept stale.
                if ($locale !== $data['language']) {
                    CourseTranslation::where('course_id', $course->id)->where('locale', $locale)->delete();
                }
                continue;
            }
            CourseTranslation::updateOrCreate(
                ['course_id' => $course->id, 'locale' => $locale],
                [
                    'title'            => $trans['title'],
                    'description'      => $trans['description'] ?? '',
                    'requirements'     => $trans['requirements'] ?? '',
                    'what_you_learn'   => $trans['what_you_learn'] ?? '',
                    'meta_title'       => $trans['title'],
                    'meta_description' => $trans['meta_description'] ?? Str::limit(strip_tags($trans['description'] ?? ''), 155),
                ]
            );
        }

        $course->recalculateDuration();

        return redirect()->route('instructor.courses.edit', ['course' => $course, 'tab' => 'info'])
            ->with('success', __('messages.course_updated'));
    }

    public function destroy(Course $course)
    {
        $this->authorizeCourse($course);
        abort_if($course->status === 'published' || $course->enrollments()->exists(), 403, __('lms.course_delete_forbidden'));

        foreach ($course->lessons()->get() as $lesson) {
            app(\App\Services\ChunkedVideoUpload::class)->deleteVideo($lesson);
        }
        Storage::disk('local')->deleteDirectory("videos/course_{$course->id}");
        Storage::disk('local')->deleteDirectory("resources/course_{$course->id}");
        $course->delete();

        return redirect()->route('instructor.courses.index')->with('success', __('messages.course_deleted'));
    }

    public function submit(Course $course)
    {
        $this->authorizeCourse($course);
        abort_if(! in_array($course->status, ['draft', 'rejected', 'unpublished']), 403);

        $course->load(['translations', 'modules.lessons.quiz.questions', 'modules.exam.questions', 'finalExam.questions']);
        $checklist = $this->publishChecklist($course);
        $missing = array_filter($checklist, fn ($item) => ! $item['ok']);

        if ($missing) {
            return back()->withErrors(['submit' => __('lms.submit_incomplete')])
                ->with('checklist_failed', true);
        }

        $course->update(['status' => 'pending', 'admin_feedback' => null]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->where('is_active', true)->get()
            ->filter(fn ($admin) => $admin->hasAdminPermission('courses'));
        Notification::send($admins, new CourseSubmittedForReview($course));

        return back()->with('success', __('messages.course_submitted'));
    }

    /**
     * Requirements before a course can be sent for review.
     *
     * @return array<string, array{ok: bool, label: string}>
     */
    public function publishChecklist(Course $course): array
    {
        $modules = $course->modules;
        $lessons = $modules->flatMap->lessons;
        $trans   = $course->translationExact($course->language);

        $videosOk = $lessons->where('type', 'video')->every(fn ($l) => $l->hasVideo());
        $min = config('lms.assessment.min_questions');
        // Every lesson except assignments is followed by a quiz of at least 10 questions.
        $quizzesOk = $lessons->isNotEmpty() && $lessons->where('type', '!=', 'assignment')
            ->every(fn ($l) => $l->quiz && $l->quiz->questions->count() >= $min['lesson']);
        $exercisesOk = $modules->isNotEmpty() && $modules->every(fn ($m) => $m->exam && $m->exam->questions->count() >= $min['module']);
        $finalOk = $course->finalExam && $course->finalExam->questions->count() >= $min['course'];
        $modulesHaveLessons = $modules->isNotEmpty() && $modules->every(fn ($m) => $m->lessons->isNotEmpty());
        $hoursOk = $course->duration_hours > 0 && $modules->every(fn ($m) => $m->duration_hours > 0);

        return [
            'info'     => ['ok' => $trans && filled($trans->title) && filled($trans->description), 'label' => __('lms.check_info')],
            'hours'    => ['ok' => $hoursOk, 'label' => __('lms.check_hours')],
            'modules'  => ['ok' => $modulesHaveLessons, 'label' => __('lms.check_modules')],
            'videos'   => ['ok' => $videosOk, 'label' => __('lms.check_videos')],
            'quizzes'  => ['ok' => $quizzesOk, 'label' => __('learn.check_lesson_quizzes', ['min' => $min['lesson']])],
            'exercises' => ['ok' => $exercisesOk, 'label' => __('learn.check_module_exercises', ['min' => $min['module']])],
            'final'    => ['ok' => $finalOk, 'label' => __('learn.check_final_evaluation', ['min' => $min['course']])],
            'thumb'    => ['ok' => (bool) $course->thumbnail, 'label' => __('lms.check_thumbnail')],
        ];
    }

    private function storeThumbnail(Request $request): ?string
    {
        if (! $request->hasFile('thumbnail')) {
            return null;
        }
        $file = $request->file('thumbnail');
        $name = time() . '_' . Str::random(8) . '.' . $file->extension();
        $file->storeAs('thumbnails', $name, 'public');
        return $name;
    }
}
