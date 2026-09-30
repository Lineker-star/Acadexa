<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A book (PDF) or audio/video document the instructor provides with a course. */
class Book extends Model
{
    protected $fillable = ['course_id', 'title', 'author', 'description', 'path', 'original_name', 'mime', 'size', 'order'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function libraryItems()
    {
        return $this->hasMany(LibraryItem::class);
    }

    /** pdf | slides | audio | video — decides which reader the app opens. */
    public function kind(): string
    {
        $mime = (string) $this->mime;
        $ext = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));
        return match (true) {
            in_array($ext, ['ppt', 'pptx'], true)  => 'slides',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            default                          => 'pdf',
        };
    }

    public function icon(): string
    {
        return match ($this->kind()) {
            'audio' => 'bi-file-earmark-music',
            'video' => 'bi-file-earmark-play',
            'slides' => 'bi-file-earmark-slides',
            default => 'bi-file-earmark-pdf',
        };
    }

    public function sizeLabel(): string
    {
        return \App\Support\Format::bytes($this->size);
    }

    public function url(): string
    {
        return route('media.book', $this);
    }
}
