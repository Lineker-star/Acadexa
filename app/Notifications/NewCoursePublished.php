<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Support\Str;

/** A new course is available in the catalogue. */
class NewCoursePublished extends LmsNotification
{
    public function __construct(public Course $course)
    {
        parent::__construct();
    }

    protected function category(): string
    {
        return self::CATEGORY_NEWS;
    }

    protected function subject(object $notifiable): string
    {
        return __('learn.notif_new_course_subject', ['course' => $this->course->title()]);
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_new_course_line', ['course' => $this->course->title(), 'instructor' => $this->course->instructor?->name]);
    }

    protected function extraLines(object $notifiable): array
    {
        return array_filter([Str::limit(trim(strip_tags($this->course->description())), 400)]);
    }

    protected function url(object $notifiable): string
    {
        return route('courses.show', $this->course->slug);
    }

    protected function actionText(object $notifiable): string
    {
        return __('learn.notif_view_course');
    }

    protected function icon(): string
    {
        return 'bi-mortarboard';
    }
}
