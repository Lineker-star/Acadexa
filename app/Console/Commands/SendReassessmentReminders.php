<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Notifications\ReassessmentReminder;
use Illuminate\Console\Command;

/**
 * Knowledge fades: some time after passing a course's final evaluation, students are
 * invited to take it again so that progression or regression can be measured.
 */
class SendReassessmentReminders extends Command
{
    protected $signature = 'acadexxa:reassessment-reminders';
    protected $description = 'Invite students to re-evaluate their knowledge of completed courses';

    public function handle(): int
    {
        $days = (int) config('lms.assessment.reassess_after_days');
        $sent = 0;

        Enrollment::whereNotNull('completed_at')
            ->where(fn ($q) => $q->whereNull('reassess_reminded_at')->orWhere('reassess_reminded_at', '<', now()->subDays($days)))
            ->whereHas('course.finalExam')
            ->with(['user', 'course.finalExam', 'course.translations'])
            ->chunkById(200, function ($enrollments) use ($days, &$sent) {
                foreach ($enrollments as $enrollment) {
                    $final = $enrollment->course->finalExam;
                    $last = $final->attempts()->where('user_id', $enrollment->user_id)->max('attempted_at');
                    if (! $last || now()->subDays($days)->lt($last) || ! $enrollment->user?->is_active) {
                        continue;
                    }
                    $enrollment->user->notify(new ReassessmentReminder($enrollment));
                    $enrollment->update(['reassess_reminded_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Re-evaluation reminders sent: {$sent}");
        return self::SUCCESS;
    }
}
