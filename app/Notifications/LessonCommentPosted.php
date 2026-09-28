<?php

namespace App\Notifications;

use App\Models\LessonComment;

/**
 * New question on a lesson (to the instructor) or a reply (to the question author).
 */
class LessonCommentPosted extends LmsNotification
{
    public function __construct(public LessonComment $comment, public bool $isReply)
    {
        parent::__construct();
    }

    protected function subject(object $notifiable): string
    {
        return $this->isReply ? __('lms.notif_reply_subject') : __('lms.notif_question_subject');
    }

    protected function line(object $notifiable): string
    {
        $key = $this->isReply ? 'lms.notif_reply_line' : 'lms.notif_question_line';
        return __($key, [
            'name'   => $this->comment->user->name,
            'lesson' => $this->comment->lesson->title(),
        ]);
    }

    protected function extraLines(object $notifiable): array
    {
        return ['« ' . \Illuminate\Support\Str::limit($this->comment->comment, 300) . ' »'];
    }

    protected function url(object $notifiable): string
    {
        if (! $this->isReply) {
            return route('instructor.qa.index');
        }
        return route('student.lessons.open', $this->comment->lesson_id) . '#comments';
    }

    protected function icon(): string
    {
        return 'bi-chat-dots';
    }
}
