<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Support\Str;

/** News of the platform published by an administrator, sent to its audience in each reader's language. */
class AnnouncementPublished extends LmsNotification
{
    public function __construct(public Announcement $announcement)
    {
        parent::__construct();
    }

    protected function category(): string
    {
        return self::CATEGORY_NEWS;
    }

    protected function subject(object $notifiable): string
    {
        return $this->announcement->titleFor();
    }

    protected function line(object $notifiable): string
    {
        return Str::limit(trim(strip_tags($this->announcement->bodyFor())), 2000);
    }

    protected function url(object $notifiable): string
    {
        return route('notifications.index');
    }

    protected function icon(): string
    {
        return 'bi-megaphone';
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => Str::limit(trim(strip_tags($this->announcement->bodyFor())), 200)] + parent::toArray($notifiable);
    }
}
