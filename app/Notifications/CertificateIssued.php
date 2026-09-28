<?php

namespace App\Notifications;

use App\Models\Certificate;

class CertificateIssued extends LmsNotification
{
    public function __construct(public Certificate $certificate)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_certificate_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('lms.notif_certificate_line', ['course' => $this->certificate->course->title()]);
    }

    protected function url(object $notifiable): string
    {
        return route('student.certificates.index');
    }

    protected function icon(): string
    {
        return 'bi-award';
    }

    protected function extraLines(object $notifiable): array
    {
        return [__('lms.notif_certificate_code', ['code' => $this->certificate->certificate_code])];
    }
}
