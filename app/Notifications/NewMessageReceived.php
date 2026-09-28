<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Support\Str;

class NewMessageReceived extends LmsNotification
{
    public function __construct(public Message $message)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return __('lms.notif_message_subject', ['name' => $this->message->user->name]);
    }

    protected function line(object $notifiable): string
    {
        return __('lms.notif_message_line', [
            'name'    => $this->message->user->name,
            'subject' => $this->message->conversation->subject,
        ]);
    }

    protected function extraLines(object $notifiable): array
    {
        return ['« ' . Str::limit($this->message->body, 300) . ' »'];
    }

    protected function url(object $notifiable): string
    {
        return route('messages.show', $this->message->conversation_id);
    }

    protected function icon(): string
    {
        return 'bi-envelope';
    }
}
