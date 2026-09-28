<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * E-mail verification is switched on by the admin setting "require_email_verification"
     * (only once outgoing mail is configured). While it is off, every account counts as verified,
     * so neither the "verified" middleware nor the verification e-mail kick in.
     */
    public function hasVerifiedEmail(): bool
    {
        if (Setting::get('require_email_verification', '0') !== '1') {
            return true;
        }
        return ! is_null($this->email_verified_at);
    }

    /** Notifications and e-mails are rendered in the user's language. */
    public function preferredLocale(): string
    {
        return in_array($this->preferred_language, config('app.supported_locales', []), true)
            ? $this->preferred_language
            : config('app.locale');
    }

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'country', 'bio',
        'preferred_language', 'instructor_status', 'trial_started_at',
        'is_active', 'admin_permissions', 'banned_at', 'ban_reason',
        'two_factor_secret', 'two_factor_confirmed_at', 'email_notifications',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    /** Granular back-office areas a regular admin can be restricted to. */
    public const ADMIN_PERMISSIONS = [
        'users', 'courses', 'categories', 'applications', 'announcements', 'cms',
        'reviews', 'certificates', 'contacts', 'translations', 'settings', 'logs', 'reports',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'trial_started_at'        => 'datetime',
            'banned_at'               => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password'                => 'hashed',
            'is_active'               => 'boolean',
            'email_notifications'     => 'boolean',
            'admin_permissions'       => 'array',
            'two_factor_secret'       => 'encrypted',
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

    public function libraryItems()
    {
        return $this->hasMany(LibraryItem::class);
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

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function conversations()
    {
        return Conversation::forUser($this);
    }

    // ─── Account state ────────────────────────────────────────────────────────

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /** Can log in and use the platform. */
    public function canAccess(): bool
    {
        return $this->is_active && ! $this->isBanned();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret && $this->two_factor_confirmed_at;
    }

    /** Super admins, and admins without a restriction list, have full access. */
    public function hasAdminPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if (! $this->isAdmin()) {
            return false;
        }
        $permissions = $this->admin_permissions;
        return empty($permissions) || in_array($permission, $permissions, true);
    }

    public function wantsEmail(): bool
    {
        return (bool) $this->email_notifications;
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

        return now()->lt($this->trialEndsAt());
    }

    public function trialEndsAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->trial_started_at) return null;

        // Setting::get() shares the cache key that Setting::set() invalidates.
        $trialDays = (int) Setting::get('trial_days', 30);
        return $this->trial_started_at->copy()->addDays($trialDays);
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
