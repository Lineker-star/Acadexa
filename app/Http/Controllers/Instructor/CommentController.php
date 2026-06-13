<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LessonComment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function reply(Request $request, LessonComment $comment)
    {
        $request->validate(['reply' => ['required', 'string', 'max:2000']]);

        $user = $request->user();

        // Only instructors of the course or admins can reply as instructor
        $course = $comment->lesson->module->course;
        abort_if($course->instructor_id !== $user->id && ! $user->isAdmin(), 403);

        LessonComment::create([
            'lesson_id' => $comment->lesson_id,
            'user_id'   => $user->id,
            'parent_id' => $comment->id,
            'comment'   => $request->reply,
        ]);

        return back()->with('success', 'Reply posted.');
    }
}
