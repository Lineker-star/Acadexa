<?php

namespace App\Notifications;

class TrialEndingSoon extends LmsNotification
{
    public function __construct(public int $daysLeft)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_trial_subject');
    }

    protected function line(object $notifiable): string
    {
        return trans_choice('lms.notif_trial_line', $this->daysLeft, ['days' => $this->daysLeft]);
    }

    protected function url(object $notifiable): string
    {
        return route('student.subscription');
    }

    protected function icon(): string
    {
        return 'bi-hourglass';
    }
}
