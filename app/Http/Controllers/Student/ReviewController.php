<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $user = $request->user();

        // Must be enrolled and course completed
        $enrollment = $user->enrollments()->where('course_id', $course->id)->first();
        abort_if(! $enrollment, 403, __('You must be enrolled to review.'));
        abort_if($enrollment->progress_percent < 100, 403, __('lms.review_requires_completion'));

        $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        return back()->with('success', __('messages.review_submitted'));
    }
}
