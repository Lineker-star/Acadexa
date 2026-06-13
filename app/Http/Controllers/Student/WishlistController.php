<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlist = $request->user()->wishlist()
            ->with(['translations', 'instructor', 'category.translations', 'reviews'])
            ->paginate(12);

        return view('student.wishlist.index', compact('wishlist'));
    }

    public function toggle(Request $request, Course $course)
    {
        $user = $request->user();
        $result = $user->wishlist()->toggle($course->id);

        $added = count($result['attached']) > 0;

        return response()->json([
            'added'   => $added,
            'message' => $added ? __('messages.wishlist_added') : __('messages.wishlist_removed'),
        ]);
    }
}
