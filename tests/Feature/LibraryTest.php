<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Course books kept in the student's account library, read in the app (online and offline). */
class LibraryTest extends LmsTestCase
{
    private function book($course): Book
    {
        Storage::disk('local')->put("books/course_{$course->id}/b.pdf", "%PDF-1.4\n%test");
        return Book::create([
            'course_id' => $course->id, 'title' => 'Manuel', 'author' => 'ZTF', 'path' => "books/course_{$course->id}/b.pdf",
            'original_name' => 'manuel.pdf', 'mime' => 'application/pdf', 'size' => 14, 'order' => 1,
        ]);
    }

    public function test_instructor_uploads_a_book_to_the_course(): void
    {
        Storage::fake('local');
        $instructor = $this->makeUser('instructor');
        $course = $this->makeCourse($instructor, ['text' => 1], ['status' => 'draft']);

        $this->actingAs($instructor)->post(route('instructor.books.store', $course), [
            'title' => 'Manuel', 'author' => 'ZTF', 'file' => UploadedFile::fake()->create('manuel.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('instructor.courses.edit', ['course' => $course, 'tab' => 'books']));

        $book = $course->books()->first();
        $this->assertSame('Manuel', $book->title);
        Storage::disk('local')->assertExists($book->path);
        $this->actingAs($instructor)->get(route('instructor.courses.edit', ['course' => $course, 'tab' => 'books']))->assertOk()->assertSee('Manuel');

        // Executables are refused.
        $this->actingAs($instructor)->post(route('instructor.books.store', $course), ['file' => UploadedFile::fake()->create('x.exe', 10)])
            ->assertSessionHasErrors('file');
    }

    public function test_library_follows_the_account_and_books_are_read_inline(): void
    {
        Storage::fake('local');
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $book = $this->book($course);

        // Not enrolled: no access.
        $this->actingAs($student)->post(route('student.library.add', $book))->assertForbidden();
        $this->actingAs($student)->get(route('media.book', $book))->assertForbidden();

        $this->enroll($student, $course);
        $this->actingAs($student)->get(route('student.library.index'))->assertOk()->assertSee(__('learn.add_to_library'));
        $this->actingAs($student)->postJson(route('student.library.add', $book))->assertOk()->assertJsonPath('item.book_id', $book->id);

        // Same list on any device: it comes from the account.
        $this->actingAs($student)->getJson(route('student.library.data'))->assertOk()
            ->assertJsonPath('user_id', $student->id)
            ->assertJsonPath('items.0.url', route('media.book', $book))
            ->assertJsonPath('items.0.kind', 'pdf');

        // Always inline: read inside the app, never saved to the device's downloads.
        $response = $this->actingAs($student)->get(route('media.book', $book))->assertOk();
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));

        // Reading position, directly and through the offline sync.
        $this->actingAs($student)->postJson(route('student.library.position', $book), ['position' => 12, 'progress' => 40])->assertOk();
        $this->actingAs($student)->postJson(route('offline.sync'), ['events' => [
            ['id' => 'p1', 'type' => 'library_position', 'book_id' => $book->id, 'position' => 15, 'progress' => 50],
        ]])->assertOk()->assertJsonPath('results.0.ok', true);
        $item = $student->libraryItems()->first();
        $this->assertSame([15, 50], [$item->position, $item->progress_percent]);

        $this->actingAs($student)->get(route('student.library.course', $course))->assertOk()->assertSee(__('learn.in_library'));
        $this->actingAs($student)->deleteJson(route('student.library.remove', $book))->assertOk();
        $this->assertSame(0, $student->libraryItems()->count());
    }

    public function test_lesson_resources_open_inline_unless_downloaded_explicitly(): void
    {
        Storage::fake('local');
        $student = $this->makeUser();
        $course = $this->makeCourse($this->makeUser('instructor'), ['text' => 1]);
        $lesson = $this->lessonsOf($course)->first();
        Storage::disk('local')->put('resources/r.pdf', '%PDF-1.4');
        $resource = \App\Models\LessonResource::create(['lesson_id' => $lesson->id, 'title' => 'Fiche', 'path' => 'resources/r.pdf', 'original_name' => 'fiche.pdf', 'mime' => 'application/pdf', 'size' => 8]);
        $this->enroll($student, $course);

        $this->assertStringStartsWith('inline', $this->actingAs($student)->get(route('media.resource', $resource))->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('attachment', $this->actingAs($student)->get(route('media.resource', $resource) . '?download=1')->headers->get('Content-Disposition'));
    }
}
