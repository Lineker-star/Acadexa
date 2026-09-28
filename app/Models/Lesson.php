<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasTranslations;

    public const TYPES = ['video', 'text', 'quiz', 'assignment'];

    public const VIDEO_UPLOAD  = 'upload';
    public const VIDEO_YOUTUBE = 'youtube';
    public const VIDEO_VIMEO   = 'vimeo';
    public const VIDEO_URL     = 'url';

    protected $fillable = [
        'module_id', 'order', 'type', 'video_source', 'video_url', 'video_path',
        'video_original_name', 'video_size', 'video_mime', 'youtube_id',
        'content', 'attachment_path', 'duration_minutes', 'is_free_preview',
        'is_downloadable', 'assignment_max_score', 'assignment_pass_score',
    ];

    protected $hidden = ['video_path'];

    protected function casts(): array
    {
        return [
            'is_free_preview'       => 'boolean',
            'is_downloadable'       => 'boolean',
            'duration_minutes'      => 'integer',
            'video_size'            => 'integer',
            'assignment_max_score'  => 'integer',
            'assignment_pass_score' => 'integer',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function translations()
    {
        return $this->hasMany(LessonTranslation::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function resources()
    {
        return $this->hasMany(LessonResource::class)->orderBy('order');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function comments()
    {
        return $this->hasMany(LessonComment::class)->whereNull('parent_id')->with('replies.user')->latest();
    }

    public function title(string $locale = null): string
    {
        return $this->translationFor($locale)?->title ?? 'Lesson';
    }

    /** Lesson body in the requested locale, falling back to the base column. */
    public function body(string $locale = null): string
    {
        $trans = $this->translationFor($locale);
        return (string) ($trans?->content ?: $this->content);
    }

    /**
     * Safe HTML for display. Older lessons were written as plain text in a textarea:
     * keep their line breaks. Rich-text content is sanitized.
     */
    public function renderedBody(string $locale = null): string
    {
        $body = $this->body($locale);
        if (trim($body) === '') {
            return '';
        }
        if ($body === strip_tags($body)) {
            return nl2br(e($body));
        }
        return \App\Support\HtmlSanitizer::clean($body);
    }

    // ─── Video ────────────────────────────────────────────────────────────────

    /** Resolved video source, including legacy rows that only have video_url. */
    public function videoKind(): ?string
    {
        if ($this->video_source) {
            return $this->video_source;
        }
        if (! $this->video_url) {
            return null;
        }
        if (static::parseYoutubeId($this->video_url)) {
            return self::VIDEO_YOUTUBE;
        }
        if (static::parseVimeoId($this->video_url)) {
            return self::VIDEO_VIMEO;
        }
        return self::VIDEO_URL;
    }

    public function hasVideo(): bool
    {
        return match ($this->videoKind()) {
            self::VIDEO_UPLOAD  => (bool) $this->video_path,
            self::VIDEO_YOUTUBE => (bool) $this->youtubeId(),
            self::VIDEO_VIMEO, self::VIDEO_URL => (bool) $this->video_url,
            default => false,
        };
    }

    public function youtubeId(): ?string
    {
        return $this->youtube_id ?: static::parseYoutubeId((string) $this->video_url);
    }

    public function vimeoId(): ?string
    {
        return static::parseVimeoId((string) $this->video_url);
    }

    /** Only videos hosted on the platform can be stored for offline viewing. */
    public function isOfflineCapable(): bool
    {
        return $this->is_downloadable && in_array($this->type, ['video', 'text'], true)
            && (! $this->hasVideo() || $this->videoKind() === self::VIDEO_UPLOAD);
    }

    public static function parseYoutubeId(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }
        $pattern = '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';
        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }

    public static function parseVimeoId(string $url): ?string
    {
        return preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m) ? $m[1] : null;
    }

    // ─── Completion rules ─────────────────────────────────────────────────────

    /** Quizzes and assignments complete through assessment, not a button. */
    public function isManuallyCompletable(): bool
    {
        return in_array($this->type, ['video', 'text'], true) && ! $this->requiresQuiz();
    }

    /** A video/text lesson is validated by passing its quiz once the quiz has enough questions. */
    public function requiresQuiz(): bool
    {
        return in_array($this->type, ['video', 'text'], true) && $this->quiz && $this->quiz->isReady();
    }

    /** The quiz a student takes on this lesson (the lesson check, or the quiz lesson itself). */
    public function studentQuiz(): ?Quiz
    {
        if ($this->type === 'quiz') {
            return $this->quiz && $this->quiz->questionCount() > 0 ? $this->quiz : null;
        }
        return $this->requiresQuiz() ? $this->quiz : null;
    }

    public function icon(): string
    {
        return match ($this->type) {
            'video'      => 'bi-play-circle',
            'quiz'       => 'bi-patch-question',
            'assignment' => 'bi-clipboard-check',
            default      => 'bi-file-text',
        };
    }
}
