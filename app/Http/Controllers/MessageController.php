<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Http\Request;

/**
 * Private messages between a student and the instructor of a course they follow.
 */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = Conversation::forUser($user)
            ->with(['student', 'instructor', 'course.translations', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('user_id', '!=', $user->id)])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        // Courses the student can write about (to open a new conversation).
        $courses = Course::whereIn('id', $user->enrollments()->select('course_id'))
            ->with(['translations', 'instructor'])->get();

        return view('messages.index', compact('conversations', 'courses'));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->hasParticipant($user), 403);

        $conversation->messages()->whereNull('read_at')->where('user_id', '!=', $user->id)->update(['read_at' => now()]);
        $user->unreadNotifications()
            ->where('type', NewMessageReceived::class)
            ->where('data->url', route('messages.show', $conversation))
            ->update(['read_at' => now()]);

        $conversation->load(['messages.user', 'student', 'instructor', 'course.translations']);

        return view('messages.show', compact('conversation'));
    }

    /** New conversation. Students write to a course's instructor; instructors to one of their students. */
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'course_id'  => ['required', 'exists:courses,id'],
            'student_id' => ['nullable', 'exists:users,id'],
            'subject'    => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string', 'max:5000'],
        ]);

        $course = Course::findOrFail($data['course_id']);

        if ($course->instructor_id === $user->id) {
            $student = User::findOrFail($data['student_id'] ?? 0);
            abort_unless(Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->exists(), 403);
            $studentId = $student->id;
        } else {
            abort_unless(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists(), 403);
            $studentId = $user->id;
        }

        $conversation = Conversation::create([
            'course_id'       => $course->id,
            'student_id'      => $studentId,
            'instructor_id'   => $course->instructor_id,
            'subject'         => $data['subject'],
            'last_message_at' => now(),
        ]);

        $this->post($conversation, $user, $data['body']);

        return redirect()->route('messages.show', $conversation)->with('success', __('lms.message_sent'));
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        abort_unless($conversation->hasParticipant($user), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $this->post($conversation, $user, $data['body']);

        return redirect()->to(route('messages.show', $conversation) . '#bottom');
    }

    private function post(Conversation $conversation, User $author, string $body): void
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $author->id,
            'body'            => $body,
        ]);
        $conversation->update(['last_message_at' => now()]);

        $conversation->loadMissing(['student', 'instructor']);
        $conversation->otherParticipant($author)->notify(new NewMessageReceived($message->load(['user', 'conversation'])));
    }
}
