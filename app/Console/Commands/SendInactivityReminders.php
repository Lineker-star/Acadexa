<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Notifications\InactivityReminder;
use Illuminate\Console\Command;

/**
 * "Pick up where you stopped": students who have an unfinished course they have not opened for
 * a while receive a reminder (config lms.reminders), at most once per repeat period and per course.
 */
class SendInactivityReminders extends Command
{
    protected $signature = 'acadexa:inactivity-reminders';
    protected $description = 'Remind students of the courses they have not opened recently';

    public function handle(): int
    {
        $inactive = now()->subDays((int) config('lms.reminders.inactive_days'));
        $repeat = now()->subDays((int) config('lms.reminders.repeat_days'));
        $sent = 0;

        Enrollment::where('progress_percent', '<', 100)
            ->where('updated_at', '<', $inactive)
            ->where(fn ($q) => $q->whereNull('inactivity_reminded_at')->orWhere('inactivity_reminded_at', '<', $repeat))
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->with(['user', 'course.translations'])
            ->chunkById(200, function ($enrollments) use (&$sent) {
                foreach ($enrollments as $enrollment) {
                    $user = $enrollment->user;
                    if (! $user || ! $user->canAccess() || ! $user->isTrialActive()) {
                        continue;
                    }
                    $user->notify(new InactivityReminder($enrollment));
                    // Keep updated_at: it is the date of the student's last activity.
                    Enrollment::withoutTimestamps(fn () => $enrollment->forceFill(['inactivity_reminded_at' => now()])->save());
                    $sent++;
                }
            });

        $this->info("Inactivity reminders sent: {$sent}");
        return self::SUCCESS;
    }
}
