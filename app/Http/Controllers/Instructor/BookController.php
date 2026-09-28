<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Book;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Books (PDF) and audio/video documents that enrolled students keep in their library. */
class BookController extends Controller
{
    use AuthorizesCourse;

    public function store(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $data = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'author'      => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file'        => [
                'required', 'file',
                'max:' . config('lms.book.max_size_mb') * 1024,
                'extensions:' . implode(',', config('lms.book.extensions')),
            ],
        ]);

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $path = $file->storeAs("books/course_{$course->id}", Str::uuid() . '.' . strtolower($file->getClientOriginalExtension()), 'local');

        Book::create([
            'course_id'     => $course->id,
            'title'         => $data['title'] ?: pathinfo($original, PATHINFO_FILENAME),
            'author'        => $data['author'] ?? null,
            'description'   => $data['description'] ?? null,
            'path'          => $path,
            'original_name' => Str::limit($original, 250, ''),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
            'order'         => (int) $course->books()->max('order') + 1,
        ]);

        return $this->back($course, __('learn.book_uploaded'));
    }

    public function update(Request $request, Book $book)
    {
        $this->authorizeCourse($book->course);
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'author'      => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $book->update($data);

        return $this->back($book->course, __('learn.book_updated'));
    }

    public function destroy(Book $book)
    {
        $course = $book->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        Storage::disk('local')->delete($book->path);
        $book->delete();

        return $this->back($course, __('learn.book_deleted'));
    }

    private function back(Course $course, string $message)
    {
        return redirect()->route('instructor.courses.edit', ['course' => $course, 'tab' => 'books'])->with('success', $message);
    }
}
