<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(private CertificateService $certificateService) {}

    public function complete(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->module->course_id)
            ->firstOrFail();

        LessonProgress::firstOrCreate([
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
        ], ['completed_at' => now()]);

        $enrollment->update(['last_lesson_id' => $lesson->id]);
        $enrollment->recalculateProgress();
        $enrollment->refresh();

        // Auto-issue certificate when 100% complete
        $message = '';
        if ($enrollment->progress_percent >= 100) {
            $cert = $this->certificateService->issue($enrollment);
            $message = __('messages.certificate_issued');
        }

        return response()->json([
            'progress' => $enrollment->progress_percent,
            'completed'=> $enrollment->progress_percent >= 100,
            'message'  => $message,
        ]);
    }

    public function comment(Request $request, Lesson $lesson)
    {
        $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        $user = $request->user();

        // Verify user is enrolled
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->module->course_id)
            ->exists();

        abort_if(! $enrolled && ! $user->isAdmin() && ! $user->isInstructor(), 403);

        LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id'   => $user->id,
            'comment'   => $request->comment,
        ]);

        return back()->with('success', __('messages.comment_posted'));
    }
}
