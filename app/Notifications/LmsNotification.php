<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for platform notifications: always stored in-app (bell icon),
 * e-mailed as well when the user keeps e-mail notifications on.
 * Queued: production must run the scheduler (see routes/console.php).
 */
abstract class LmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract protected function subject(object $notifiable): string;

    abstract protected function line(object $notifiable): string;

    abstract protected function url(object $notifiable): string;

    protected function icon(): string
    {
        return 'bi-bell';
    }

    protected function actionText(object $notifiable): string
    {
        return __('lms.notif_open');
    }

    /** Extra paragraphs shown only in the e-mail. */
    protected function extraLines(object $notifiable): array
    {
        return [];
    }

    /** Notifications that must always be e-mailed (e.g. security) override this. */
    protected function forceMail(): bool
    {
        return false;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($this->forceMail() || (method_exists($notifiable, 'wantsEmail') && $notifiable->wantsEmail())) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject($notifiable))
            ->greeting(__('lms.mail_greeting', ['name' => $notifiable->name]))
            ->line($this->line($notifiable));

        foreach ($this->extraLines($notifiable) as $extra) {
            $mail->line($extra);
        }

        return $mail->action($this->actionText($notifiable), $this->url($notifiable))
            ->salutation(__('lms.mail_salutation'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => $this->subject($notifiable),
            'message' => $this->line($notifiable),
            'url'     => $this->url($notifiable),
            'icon'    => $this->icon(),
        ];
    }
}
