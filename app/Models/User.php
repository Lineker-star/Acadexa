<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class User extends Authenticatable 
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'country', 'bio',
        'preferred_language', 'instructor_status', 'trial_started_at',
        'is_active', 'admin_permissions',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'trial_started_at'   => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'admin_permissions'  => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['progress_percent', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlist()
    {
        return $this->belongsToMany(Course::class, 'wishlists')->withTimestamps();
    }

    public function instructorApplication()
    {
        return $this->hasOne(InstructorApplication::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ─── Role helpers ─────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor' && $this->instructor_status === 'confirmed';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isPendingInstructor(): bool
    {
        return $this->instructor_status === 'pending';
    }

    // ─── Trial logic ──────────────────────────────────────────────────────────

    public function isTrialActive(): bool
    {
        if ($this->isAdmin() || $this->isInstructor()) {
            return true;
        }

        if (! $this->trial_started_at) {
            return false;
        }

        $trialDays = (int) Cache::remember('trial_days_setting', 3600, function () {
            return Setting::where('key', 'trial_days')->value('value') ?? 30;
        });

        return now()->lt($this->trial_started_at->addDays($trialDays));
    }

    public function trialEndsAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->trial_started_at) return null;

        $trialDays = (int) (Setting::where('key', 'trial_days')->value('value') ?? 30);
        return $this->trial_started_at->addDays($trialDays);
    }

    public function trialDaysLeft(): int
    {
        $endsAt = $this->trialEndsAt();
        if (! $endsAt) return 0;
        return max(0, (int) now()->diffInDays($endsAt, false));
    }

    // ─── Avatar URL ───────────────────────────────────────────────────────────

    public function avatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        $initials = urlencode(substr($this->name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initials}&background=0A2A5E&color=fff&size=128";
    }
}
