<?php

namespace App\Http\Controllers;

use App\Models\User;

/**
 * One-click unsubscribe from a signed link placed at the bottom of every e-mail.
 * "news" stops the platform news and reminders; "activity" stops the course activity e-mails.
 * In-app notifications (bell) are kept, and security e-mails are always sent.
 */
class EmailPreferenceController extends Controller
{
    public function unsubscribe(User $user, string $type)
    {
        $user->forceFill([$type === 'news' ? 'email_news' : 'email_notifications' => false])->save();

        return view('email-unsubscribed', ['type' => $type, 'email' => $user->email]);
    }
}
