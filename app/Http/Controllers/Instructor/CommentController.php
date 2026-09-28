<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LessonComment;
use App\Notifications\LessonCommentPosted;
use Illuminate\Http\Request;

/** Q&A inbox: questions students ask under the instructor's lessons. */
class CommentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = LessonComment::whereNull('parent_id')
            ->with(['user', 'lesson.translations', 'lesson.module.course.translations', 'replies.user'])
            ->whereHas('lesson.module.course', fn ($q) => $q->where('instructor_id', $user->id));

        $filter = $request->get('filter', 'unanswered');
        if ($filter === 'unanswered') {
            // "Unanswered" = no reply from the instructor yet.
            $query->whereDoesntHave('replies', fn ($q) => $q->where('user_id', $user->id));
        }

        $questions = $query->latest()->paginate(20)->withQueryString();

        return view('instructor.questions.index', compact('questions', 'filter'));
    }

    public function reply(Request $request, LessonComment $comment)
    {
        $request->validate(['reply' => ['required', 'string', 'max:2000']]);

        $user = $request->user();
        $course = $comment->lesson->module->course;
        abort_if($course->instructor_id !== $user->id && ! $user->isAdmin(), 403);

        $reply = LessonComment::create([
            'lesson_id' => $comment->lesson_id,
            'user_id'   => $user->id,
            'parent_id' => $comment->id,
            'comment'   => $request->reply,
        ]);

        if ($comment->user_id !== $user->id) {
            $comment->user->notify(new LessonCommentPosted($reply->load(['user', 'lesson']), true));
        }

        return back()->with('success', __('lms.reply_posted'));
    }
}
