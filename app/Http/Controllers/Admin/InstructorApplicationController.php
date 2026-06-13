<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InstructorApplication;
use Illuminate\Http\Request;

class InstructorApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = InstructorApplication::with('user');

        if ($request->filled('status')) $query->where('status', $request->status);

        $applications = $query->latest()->paginate(20)->withQueryString();
        return view('admin.instructor-applications.index', compact('applications'));
    }

    public function show(InstructorApplication $application)
    {
        $application->load('user');
        return view('admin.instructor-applications.show', compact('application'));
    }

    public function approve(InstructorApplication $application)
    {
        $application->update(['status' => 'approved']);
        $application->user->update(['instructor_status' => 'confirmed', 'role' => 'instructor']);
        ActivityLog::record('instructor_approve', "Approved instructor #{$application->user_id}");
        return back()->with('success', 'Instructor application approved.');
    }

    public function reject(Request $request, InstructorApplication $application)
    {
        $request->validate(['admin_notes' => ['nullable', 'string', 'max:1000']]);
        $application->update(['status' => 'rejected', 'admin_notes' => $request->admin_notes]);
        $application->user->update(['instructor_status' => 'rejected']);
        ActivityLog::record('instructor_reject', "Rejected instructor #{$application->user_id}");
        return back()->with('success', 'Application rejected.');
    }
}
