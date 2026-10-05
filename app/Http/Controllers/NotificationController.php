<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(25);
        return view('notifications.index', compact('notifications'));
    }

    /** Marks one notification read, then follows its link. */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('notifications.index');
        // Only follow links to this site.
        if (! str_starts_with($url, url('/'))) {
            $url = route('notifications.index');
        }

        return redirect()->to($url);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', __('lms.notifications_all_read'));
    }

    /** Toggle for e-mail notifications (profile page). */
    public function preferences(Request $request)
    {
        $request->user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'email_news'          => $request->boolean('email_news'),
        ]);
        return back()->with('success', __('lms.preferences_saved'));
    }
}
