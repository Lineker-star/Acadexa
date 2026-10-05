<?php

namespace App\Notifications;

/** Security alert: the password of the account was changed. Always e-mailed. */
class PasswordChanged extends LmsNotification
{
    protected function subject(object $notifiable): string
    {
        return __('learn.notif_password_subject');
    }

    protected function line(object $notifiable): string
    {
        return __('learn.notif_password_line');
    }

    protected function extraLines(object $notifiable): array
    {
        return [__('learn.notif_password_warning')];
    }

    protected function url(object $notifiable): string
    {
        return route('password.request');
    }

    protected function actionText(object $notifiable): string
    {
        return __('learn.notif_password_action');
    }

    protected function icon(): string
    {
        return 'bi-shield-lock';
    }

    protected function forceMail(): bool
    {
        return true;
    }
}
