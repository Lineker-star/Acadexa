<?php

namespace App\Notifications;

use App\Models\CourseAnnouncement;
use Illuminate\Support\Str;

class CourseAnnouncementPosted extends LmsNotification
{
    public function __construct(public CourseAnnouncement $announcement)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_announcement_subject', ['course' => $this->announcement->course->title()]);
    }

    protected function line(object $notifiable): string
    {
        return $this->announcement->title;
    }

    protected function extraLines(object $notifiable): array
    {
        return [Str::limit($this->announcement->body, 1500)];
    }

    protected function url(object $notifiable): string
    {
        return route('student.course.announcements', $this->announcement->course_id);
    }

    protected function icon(): string
    {
        return 'bi-megaphone';
    }
}
