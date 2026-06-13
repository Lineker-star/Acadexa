<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['user', 'course.translations'])
            ->when($request->flagged, fn($q) => $q->where('is_flagged', true))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function flag(Review $review)
    {
        $review->update(['is_flagged' => ! $review->is_flagged]);
        return back()->with('success', 'Review flag toggled.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Review deleted.');
    }
}
