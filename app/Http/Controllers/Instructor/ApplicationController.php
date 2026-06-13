<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorApplication;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        abort_if($user->instructor_status === 'confirmed', 403);

        if ($user->instructorApplication()->exists()) {
            return back()->with('info', __('messages.already_applied'));
        }

        $data = $request->validate([
            'bio'            => ['required', 'string', 'min:100', 'max:3000'],
            'credentials'    => ['required', 'string', 'min:50', 'max:2000'],
            'expertise'      => ['required', 'string', 'max:255'],
            'sample_content' => ['nullable', 'string', 'max:2000'],
        ]);

        InstructorApplication::create([...$data, 'user_id' => $user->id]);

        $user->update(['instructor_status' => 'pending']);

        return redirect()->route('home')
            ->with('success', __('messages.application_submitted'));
    }
}
