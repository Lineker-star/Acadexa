<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->search, fn($q) => $q->where('action', 'like', "%{$request->search}%")->orWhere('description', 'like', "%{$request->search}%"))
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity-logs.index', compact('logs'));
    }

    public function clear()
    {
        ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
        return back()->with('success', 'Activity logs older than 30 days have been cleared.');
    }
}
