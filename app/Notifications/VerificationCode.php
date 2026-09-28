<?php

namespace App\Notifications;

use App\Services\EmailCode;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Six-digit code for registration or login (e-mail only, never stored in the notification bell). */
class VerificationCode extends Notification
{
    public function __construct(public string $code, public string $purpose) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('learn.code_mail_subject_' . $this->purpose, ['code' => $this->code]))
            ->greeting(__('lms.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('learn.code_mail_intro_' . $this->purpose))
            ->line('**' . $this->code . '**')
            ->line(__('learn.code_mail_expiry', ['minutes' => EmailCode::TTL_MINUTES]))
            ->line(__('learn.code_mail_ignore'))
            ->salutation(__('lms.mail_salutation'));
    }
}
