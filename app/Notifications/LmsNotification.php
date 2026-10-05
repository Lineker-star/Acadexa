<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * Base for platform notifications: always stored in-app (bell icon), and e-mailed according to
 * the recipient's preferences:
 *  - "activity" (my courses, grades, messages…)  → users.email_notifications
 *  - "news" (platform news, new courses, reminders) → users.email_news
 * Every e-mail ends with a link to the preferences and a one-click unsubscribe link.
 * Queued: production must run the scheduler (see routes/console.php).
 */
abstract class LmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const CATEGORY_ACTIVITY = 'activity';
    public const CATEGORY_NEWS = 'news';

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

    /** Which preference decides whether this notification is e-mailed. */
    protected function category(): string
    {
        return self::CATEGORY_ACTIVITY;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($this->forceMail() || $this->wantsMail($notifiable)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    private function wantsMail(object $notifiable): bool
    {
        if ($this->category() === self::CATEGORY_NEWS) {
            return method_exists($notifiable, 'wantsNewsEmail') && $notifiable->wantsNewsEmail();
        }
        return method_exists($notifiable, 'wantsEmail') && $notifiable->wantsEmail();
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

        $mail->action($this->actionText($notifiable), $this->url($notifiable))
            ->salutation(__('lms.mail_salutation'));

        // Security messages cannot be turned off, so they carry no unsubscribe link.
        if (! $this->forceMail() && isset($notifiable->id)) {
            $unsubscribe = URL::signedRoute('email.unsubscribe', ['user' => $notifiable->id, 'type' => $this->category()]);
            $mail->line(new HtmlString(
                '<span style="font-size:12px;color:#9ca3af">' . e(__('learn.mail_footer_' . $this->category()))
                . ' <a href="' . e(route('student.profile.edit')) . '" style="color:#9ca3af;white-space:nowrap">' . e(__('learn.mail_manage')) . '</a>'
                . ' · <a href="' . e($unsubscribe) . '" style="color:#9ca3af;white-space:nowrap">' . e(__('learn.mail_unsubscribe')) . '</a></span>'
            ));
        }

        return $mail;
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
