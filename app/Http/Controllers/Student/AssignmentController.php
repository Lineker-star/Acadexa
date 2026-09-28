<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Lesson;
use App\Notifications\AssignmentSubmitted;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    public function __construct(private ProgressService $progress) {}

    public function submit(Request $request, Lesson $lesson)
    {
        abort_unless($lesson->type === 'assignment', 404);

        $user = $request->user();
        $enrollment = $this->progress->enrollmentFor($user, $lesson);
        abort_unless($enrollment, 403);
        abort_unless($this->progress->isUnlocked($enrollment, $lesson), 423, __('lms.lesson_locked'));

        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:50000'],
            'file'    => [
                'nullable', 'file',
                'max:' . config('lms.submission.max_size_mb') * 1024,
                'extensions:' . implode(',', config('lms.submission.extensions')),
            ],
        ]);

        $existing = AssignmentSubmission::where('lesson_id', $lesson->id)->where('user_id', $user->id)->first();
        if ($existing?->isPassed()) {
            throw ValidationException::withMessages(['content' => __('lms.assignment_already_passed')]);
        }
        if (blank($data['content'] ?? null) && ! $request->hasFile('file') && ! $existing?->file_path) {
            throw ValidationException::withMessages(['content' => __('lms.assignment_empty')]);
        }

        $attributes = [
            'content'      => $data['content'] ?? null,
            'status'       => 'submitted',
            'submitted_at' => now(),
            'score'        => null,
            'graded_at'    => null,
            'graded_by'    => null,
        ];

        if ($request->hasFile('file')) {
            if ($existing?->file_path) {
                Storage::disk('local')->delete($existing->file_path);
            }
            $file = $request->file('file');
            $attributes['file_path'] = $file->storeAs(
                "submissions/lesson_{$lesson->id}",
                $user->id . '_' . Str::uuid() . '.' . strtolower($file->getClientOriginalExtension()),
                'local'
            );
            $attributes['original_name'] = Str::limit($file->getClientOriginalName(), 250, '');
        }

        $submission = AssignmentSubmission::updateOrCreate(
            ['lesson_id' => $lesson->id, 'user_id' => $user->id],
            $attributes
        );

        $instructor = $lesson->module->course->instructor;
        $instructor->notify(new AssignmentSubmitted($submission->load(['user', 'lesson'])));

        return redirect()->to(route('student.courses.player', $enrollment) . '?lesson=' . $lesson->id)
            ->with('success', __('lms.assignment_submitted'));
    }
}
