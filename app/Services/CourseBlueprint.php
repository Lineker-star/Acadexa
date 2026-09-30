<?php

namespace App\Services;

use App\Http\Controllers\Instructor\IntroVideoController;
use App\Models\Book;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranslation;
use App\Models\Module;
use App\Models\ModuleTranslation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds a course from the creation wizard:
 *  - the structure: modules (name, hours) and the number of lessons of each one,
 *  - the content, in the order of the lessons:
 *      1. uploaded videos (one per lesson),
 *      2. a YouTube video, or a YouTube playlist (one playlist video per remaining lesson),
 *  - course materials (PDF / PowerPoint) that students keep in their library, offline too.
 * Lessons left without a video become text lessons the instructor completes afterwards.
 */
class CourseBlueprint
{
    /**
     * @param  array<int, array{title: string, hours: ?float, lessons: int}>  $modules
     * @param  array<int, array{token: string, name: string}>  $videos
     * @param  array<int, array{token: string, name: string}>  $documents
     */
    public function build(Course $course, int $userId, array $modules, array $videos, ?string $youtubeUrl, array $documents): void
    {
        $locale = $course->language;
        $lessons = $this->structure($course, $modules, max(0, count($videos)), $locale);

        // 1. Uploaded videos, in order.
        $next = 0;
        foreach ($videos as $video) {
            $upload = IntroVideoController::pending($userId, $video['token']);
            if (! $upload || ! isset($lessons[$next])) {
                continue;
            }
            $lesson = $lessons[$next++];
            $target = "videos/course_{$course->id}/" . Str::uuid() . '.' . $upload['ext'];
            Storage::disk('local')->move($upload['path'], $target);
            $lesson->update([
                'type' => 'video', 'video_source' => Lesson::VIDEO_UPLOAD, 'video_path' => $target,
                'video_original_name' => Str::limit($video['name'], 250, ''), 'video_size' => $upload['size'], 'video_mime' => $upload['mime'],
            ]);
            $this->rename($lesson, $locale, $this->titleFromFile($video['name']));
        }

        // 2. YouTube: one video, or one playlist video per remaining lesson.
        if ($youtubeUrl) {
            $playlist = Lesson::parseYoutubePlaylistId($youtubeUrl);
            $videoId = Lesson::parseYoutubeId($youtubeUrl);
            $index = 0;
            foreach (array_slice($lessons, $next) as $lesson) {
                $lesson->update([
                    'type' => 'video', 'video_source' => Lesson::VIDEO_YOUTUBE, 'video_url' => $youtubeUrl,
                    'youtube_id' => $playlist ? null : $videoId,
                    'youtube_playlist_id' => $playlist, 'youtube_playlist_index' => $playlist ? $index++ : null,
                ]);
                if (! $playlist) {
                    break; // a single video goes to one lesson
                }
            }
        }

        // 3. Course materials → course books (library, offline).
        foreach ($documents as $i => $document) {
            $upload = IntroVideoController::pending($userId, $document['token']);
            if (! $upload) {
                continue;
            }
            $target = "books/course_{$course->id}/" . Str::uuid() . '.' . $upload['ext'];
            Storage::disk('local')->move($upload['path'], $target);
            Book::create([
                'course_id' => $course->id, 'title' => $this->titleFromFile($document['name']),
                'path' => $target, 'original_name' => Str::limit($document['name'], 250, ''),
                'mime' => $upload['mime'], 'size' => $upload['size'], 'order' => $i + 1,
            ]);
        }

        $course->recalculateDuration();
    }

    /**
     * Creates modules and their lessons ("Lesson 1", "Lesson 2"…). The last module grows if there
     * are more uploaded videos than lessons, so that no video is lost.
     *
     * @return array<int, Lesson> lessons in curriculum order
     */
    private function structure(Course $course, array $modules, int $minimumLessons, string $locale): array
    {
        $modules = $modules ?: [['title' => __('lms.module', [], $locale) . ' 1', 'hours' => null, 'lessons' => 1]];
        $planned = array_sum(array_column($modules, 'lessons'));
        if ($minimumLessons > $planned) {
            $modules[count($modules) - 1]['lessons'] += $minimumLessons - $planned;
        }
        $defaultHours = round(max(0.5, (float) $course->duration_hours / count($modules)), 1);

        $lessons = [];
        $number = 0;
        foreach (array_values($modules) as $m => $data) {
            $module = Module::create([
                'course_id' => $course->id, 'order' => $m + 1, 'title' => $data['title'],
                'duration_hours' => $data['hours'] ?: $defaultHours,
            ]);
            ModuleTranslation::create(['module_id' => $module->id, 'locale' => $locale, 'title' => $data['title']]);

            for ($l = 1; $l <= max(1, (int) $data['lessons']); $l++) {
                $lesson = Lesson::create(['module_id' => $module->id, 'order' => $l, 'type' => 'text']);
                LessonTranslation::create([
                    'lesson_id' => $lesson->id, 'locale' => $locale,
                    'title' => __('learn.lesson_n', ['number' => ++$number], $locale),
                ]);
                $lessons[] = $lesson;
            }
        }
        return $lessons;
    }

    private function rename(Lesson $lesson, string $locale, string $title): void
    {
        LessonTranslation::where('lesson_id', $lesson->id)->where('locale', $locale)->update(['title' => $title]);
    }

    /** "02_Introduction-au-web.mp4" → "Introduction au web". */
    private function titleFromFile(string $name): string
    {
        $title = trim(preg_replace('/[_\-]+/', ' ', preg_replace('/^\d+[\s._-]*/', '', pathinfo($name, PATHINFO_FILENAME))));
        return Str::limit($title !== '' ? Str::ucfirst($title) : pathinfo($name, PATHINFO_FILENAME), 250, '');
    }
}
