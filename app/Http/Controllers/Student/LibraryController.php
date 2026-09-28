<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Course;
use App\Models\LibraryItem;
use App\Services\LessonAccess;
use Illuminate\Http\Request;

/**
 * The student's library. Books are "downloaded" into the account, not to the device's
 * files: the list follows the student on every device they sign in to, and the app keeps
 * a private offline copy (Cache Storage) that is wiped on logout. Books are read inside
 * the app (PDF reader, audio/video player), online or offline.
 */
class LibraryController extends Controller
{
    public function __construct(private LessonAccess $access) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $items = $user->libraryItems()->with(['book.course.translations'])->latest('updated_at')->get()
            ->filter(fn ($item) => $item->book && $this->access->canViewBook($user, $item->book));

        // Books of the student's courses that are not in the library yet.
        $courseIds = $user->enrollments()->pluck('course_id');
        $suggested = Book::whereIn('course_id', $courseIds)->whereNotIn('id', $items->pluck('book_id'))
            ->with('course.translations')->orderBy('course_id')->orderBy('order')->get();

        return view('student.library.index', compact('items', 'suggested'));
    }

    /** Library as JSON: used by the app to keep offline copies in sync on every device. */
    public function data(Request $request)
    {
        $user = $request->user();
        $items = $user->libraryItems()->with(['book.course.translations'])->get()
            ->filter(fn ($item) => $item->book && $this->access->canViewBook($user, $item->book))
            ->map(fn (LibraryItem $item) => $this->payload($item))->values();

        return response()->json(['user_id' => $user->id, 'items' => $items])->header('Cache-Control', 'no-store');
    }

    public function course(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($user->enrollments()->where('course_id', $course->id)->exists() || $user->isAdmin() || $course->instructor_id === $user->id, 403);

        $course->load(['translations', 'books']);
        $inLibrary = $user->libraryItems()->whereIn('book_id', $course->books->pluck('id'))->pluck('book_id')->all();
        $enrollment = $user->enrollments()->where('course_id', $course->id)->first();

        return view('student.library.course', compact('course', 'inLibrary', 'enrollment'));
    }

    public function add(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($this->access->canViewBook($user, $book), 403);

        $item = LibraryItem::firstOrCreate(['user_id' => $user->id, 'book_id' => $book->id]);
        $item->load('book.course.translations');

        if ($request->expectsJson()) {
            return response()->json(['message' => __('learn.book_added'), 'item' => $this->payload($item)]);
        }
        return back()->with('success', __('learn.book_added'));
    }

    public function remove(Request $request, Book $book)
    {
        $request->user()->libraryItems()->where('book_id', $book->id)->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('learn.book_removed')]);
        }
        return back()->with('success', __('learn.book_removed'));
    }

    /** Reading position (PDF page or media second), shared across the student's devices. */
    public function position(Request $request, Book $book)
    {
        $data = $request->validate([
            'position' => ['required', 'integer', 'min:0', 'max:10000000'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $item = $request->user()->libraryItems()->where('book_id', $book->id)->first();
        abort_unless($item, 404);
        $item->update([
            'position'         => $data['position'],
            'progress_percent' => max($item->progress_percent, (int) ($data['progress'] ?? 0)),
            'last_opened_at'   => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function payload(LibraryItem $item): array
    {
        $book = $item->book;
        return [
            'book_id'  => $book->id,
            'title'    => $book->title,
            'author'   => $book->author,
            'course'   => $book->course?->title(),
            'kind'     => $book->kind(),
            'mime'     => $book->mime,
            'size'     => (int) $book->size,
            'url'      => route('media.book', $book),
            'position' => $item->position,
            'progress' => $item->progress_percent,
        ];
    }
}
