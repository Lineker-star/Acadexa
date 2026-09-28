<?php

namespace App\Notifications;

class InstructorApplicationReviewed extends LmsNotification
{
    public function __construct(public bool $approved, public ?string $notes = null)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return $this->approved ? __('lms.notif_application_approved_subject') : __('lms.notif_application_rejected_subject');
    }

    protected function line(object $notifiable): string
    {
        return $this->approved ? __('lms.notif_application_approved_line') : __('lms.notif_application_rejected_line');
    }

    protected function extraLines(object $notifiable): array
    {
        return $this->notes ? [__('lms.notif_feedback', ['feedback' => $this->notes])] : [];
    }

    protected function url(object $notifiable): string
    {
        return $this->approved ? route('instructor.dashboard') : route('become-instructor');
    }

    protected function icon(): string
    {
        return 'bi-mortarboard';
    }
}
