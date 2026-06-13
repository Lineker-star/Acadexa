<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = ['user_id', 'course_id', 'certificate_code', 'issued_at', 'pdf_path'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'ACADEXA-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . date('Y');
        } while (self::where('certificate_code', $code)->exists());

        return $code;
    }

    public function downloadUrl(): string
    {
        return route('student.certificates.download', $this->id);
    }

    public function verifyUrl(): string
    {
        return route('certificate.verify', $this->certificate_code);
    }
}
