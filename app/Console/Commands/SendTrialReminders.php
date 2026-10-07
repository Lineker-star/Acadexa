<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TrialEndingSoon;
use Illuminate\Console\Command;

/** Warns students a few days before their free access ends (config lms.trial_reminder_days). */
class SendTrialReminders extends Command
{
    protected $signature = 'acadexxa:trial-reminders';
    protected $description = 'Notify students whose trial ends soon';

    public function handle(): int
    {
        $days = config('lms.trial_reminder_days', [3, 1]);
        $sent = 0;

        User::where('role', 'student')->where('is_active', true)->whereNull('banned_at')
            ->whereNotNull('trial_started_at')
            ->chunkById(500, function ($users) use ($days, &$sent) {
                foreach ($users as $user) {
                    $endsAt = $user->trialEndsAt();
                    if (! $endsAt || $endsAt->isPast()) {
                        continue;
                    }
                    $left = (int) ceil(now()->diffInHours($endsAt) / 24);
                    if (in_array($left, $days, true)) {
                        $user->notify(new TrialEndingSoon($left));
                        $sent++;
                    }
                }
            });

        $this->info("Trial reminders sent: {$sent}");
        return self::SUCCESS;
    }
}
