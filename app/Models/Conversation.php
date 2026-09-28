<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['course_id', 'student_id', 'instructor_id', 'subject', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->oldest('id');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(fn ($q) => $q->where('student_id', $user->id)->orWhere('instructor_id', $user->id));
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, [$this->student_id, $this->instructor_id], true);
    }

    public function otherParticipant(User $user): User
    {
        return $user->id === $this->student_id ? $this->instructor : $this->student;
    }

    public function unreadCountFor(User $user): int
    {
        return $this->messages()->whereNull('read_at')->where('user_id', '!=', $user->id)->count();
    }
}
