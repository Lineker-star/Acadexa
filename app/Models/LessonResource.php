<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonResource extends Model
{
    protected $fillable = ['lesson_id', 'title', 'path', 'original_name', 'mime', 'size', 'order'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function sizeLabel(): string
    {
        return \App\Support\Format::bytes($this->size);
    }

    public function icon(): string
    {
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return match (true) {
            $ext === 'pdf'                                 => 'bi-file-earmark-pdf',
            in_array($ext, ['doc', 'docx', 'odt'])         => 'bi-file-earmark-word',
            in_array($ext, ['xls', 'xlsx', 'csv', 'ods'])  => 'bi-file-earmark-excel',
            in_array($ext, ['ppt', 'pptx', 'odp'])         => 'bi-file-earmark-slides',
            in_array($ext, ['zip', 'rar', '7z'])           => 'bi-file-earmark-zip',
            in_array($ext, ['png', 'jpg', 'jpeg', 'webp']) => 'bi-file-earmark-image',
            in_array($ext, ['mp3', 'wav', 'm4a'])          => 'bi-file-earmark-music',
            default                                        => 'bi-file-earmark',
        };
    }
}
