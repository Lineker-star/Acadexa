<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A book saved in a student's account library (available on every device they sign in to). */
class LibraryItem extends Model
{
    protected $fillable = ['user_id', 'book_id', 'position', 'progress_percent', 'last_opened_at'];

    protected function casts(): array
    {
        return [
            'position'         => 'integer',
            'progress_percent' => 'integer',
            'last_opened_at'   => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
