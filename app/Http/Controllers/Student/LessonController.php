<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\LessonWatchState;
use App\Notifications\LessonCommentPosted;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(private ProgressService $progress) {}

    /**
     * Marks a video/text lesson complete. Videos hosted on the platform or on YouTube
     * must have been watched to at least config('lms.video_completion_ratio').
     */
    public function complete(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $enrollment = $this->progress->enrollmentFor($user, $lesson);
        abort_unless($enrollment, 403);

        if (! $lesson->isManuallyCompletable()) {
            return response()->json(['message' => __('lms.complete_via_assessment')], 422);
        }
        if (! $this->progress->isUnlocked($enrollment, $lesson)) {
            return response()->json(['message' => __('lms.lesson_locked')], 423);
        }

        if ($this->requiresWatching($lesson)) {
            $state = LessonWatchState::where('user_id', $user->id)->where('lesson_id', $lesson->id)->first();
            $ratio = config('lms.video_completion_ratio');
            if ($state && $state->duration_seconds > 0 && $state->watchedRatio() < $ratio) {
                return response()->json([
                    'message' => __('lms.watch_more', ['percent' => (int) round($ratio * 100)]),
                    'watched' => round($state->watchedRatio() * 100),
                ], 422);
            }
        }

        $result = $this->progress->complete($enrollment, $lesson);

        return response()->json($result + [
            'message' => $result['certificate_issued'] ? __('messages.certificate_issued') : __('lms.lesson_completed'),
        ]);
    }

    /**
     * Periodic playback report: { position, duration, watched_delta }.
     * watched_delta counts seconds actually played since the last report, so skipping
     * ahead in the timeline does not count as watching.
     */
    public function watch(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        abort_unless($this->progress->enrollmentFor($user, $lesson), 403);

        $data = $request->validate([
            'position'      => ['required', 'numeric', 'min:0'],
            'duration'      => ['required', 'numeric', 'min:0'],
            'watched_delta' => ['required', 'numeric', 'min:0'],
        ]);

        $state = LessonWatchState::firstOrNew(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $duration = (int) round($data['duration']) ?: $state->duration_seconds;
        // A report covers at most ~2 minutes of playback (offline sync batches included).
        $delta = (int) min(round($data['watched_delta']), 120);

        $state->fill([
            'position_seconds'    => (int) $data['position'],
            'duration_seconds'    => $duration,
            'max_watched_seconds' => $duration > 0 ? min($duration, $state->max_watched_seconds + $delta) : $state->max_watched_seconds + $delta,
        ])->save();

        return response()->json([
            'watched_percent' => round($state->watchedRatio() * 100),
            'can_complete'    => $state->watchedRatio() >= config('lms.video_completion_ratio'),
        ]);
    }

    public function comment(Request $request, Lesson $lesson)
    {
        $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        $user = $request->user();
        $course = $lesson->module->course;
        $enrolled = (bool) $this->progress->enrollmentFor($user, $lesson);
        abort_if(! $enrolled && ! $this->progress->canManage($user, $course), 403);

        $comment = LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id'   => $user->id,
            'comment'   => $request->comment,
        ]);

        if ($course->instructor_id !== $user->id) {
            $course->instructor->notify(new LessonCommentPosted($comment->load(['user', 'lesson']), false));
        }

        return redirect()->to(url()->previous() . '#comments')->with('success', __('messages.comment_posted'));
    }

    /** Watch time is only measurable for our own player and YouTube's IFrame API. */
    private function requiresWatching(Lesson $lesson): bool
    {
        return $lesson->type === 'video'
            && in_array($lesson->videoKind(), [Lesson::VIDEO_UPLOAD, Lesson::VIDEO_YOUTUBE], true);
    }
}
