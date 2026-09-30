<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Course creation wizard: structure (modules + number of lessons) and content — YouTube video or
 * playlist, uploaded videos, PDF/PowerPoint materials (at least one). Every role but students.
 */
class CourseCreationContentTest extends LmsTestCase
{
    private function payload(array $extra = []): array
    {
        return array_merge([
            'language' => 'fr', 'title' => 'Excel', 'description' => 'Tableurs', 'category_id' => $this->makeCategory()->id,
            'level' => 'beginner', 'duration_hours' => 10,
            'modules' => [['title' => 'Bases', 'lessons' => 2], ['title' => 'Formules', 'lessons' => 1, 'hours' => 4]],
        ], $extra);
    }

    /** Uploads a file through the chunk endpoint and returns its token. */
    private function upload($user, string $kind, string $name, string $content): string
    {
        return $this->actingAs($user)->post(route('instructor.intro-video.chunk'), [
            'kind' => $kind, 'upload_id' => substr(md5($name . microtime()), 0, 16), 'index' => 0, 'total' => 1,
            'filename' => $name, 'size' => strlen($content), 'chunk' => UploadedFile::fake()->createWithContent('chunk', $content),
        ])->assertOk()->assertJson(['done' => true])->json('token');
    }

    private function mp4(): string
    {
        return "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom" . str_repeat("\x00", 500);
    }

    public function test_every_role_except_students_can_create_courses(): void
    {
        $this->actingAs($this->makeUser('student'))->get(route('instructor.courses.create'))->assertForbidden();
        foreach (['instructor', 'admin', 'super_admin'] as $role) {
            $this->actingAs($this->makeUser($role))->get(route('instructor.courses.create'))->assertOk();
        }
    }

    public function test_at_least_one_content_is_required(): void
    {
        $this->actingAs($this->makeUser('admin'))->post(route('instructor.courses.store'), $this->payload())
            ->assertSessionHasErrors('content');
        $this->assertSame(0, Course::count());
    }

    public function test_structure_and_youtube_playlist_fill_the_lessons(): void
    {
        $instructor = $this->makeUser('instructor');
        $this->actingAs($instructor)->post(route('instructor.courses.store'), $this->payload([
            'content_youtube_url' => 'https://www.youtube.com/playlist?list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG',
        ]))->assertSessionHasNoErrors();

        $course = Course::first();
        $modules = $course->modules()->with('lessons.translations')->get();
        $this->assertSame(['Bases', 'Formules'], $modules->map(fn ($m) => $m->title('fr'))->all());
        $this->assertSame([2, 1], $modules->map(fn ($m) => $m->lessons->count())->all());
        $this->assertEquals('4.0', $modules[1]->duration_hours);

        $lessons = $modules->flatMap->lessons;
        $this->assertSame([0, 1, 2], $lessons->pluck('youtube_playlist_index')->all());
        $this->assertTrue($lessons->every(fn ($l) => $l->videoKind() === 'youtube' && $l->hasVideo()));
        $this->assertSame('Leçon 1', $lessons[0]->title('fr'));

        // The player plays video n° of the playlist and offers the next one.
        $student = $this->makeUser();
        $course->update(['status' => 'published']);
        $enrollment = $this->enroll($student, $course);
        $this->actingAs($student)->get(route('student.courses.player', $enrollment) . '?lesson=' . $lessons[1]->id)->assertOk()
            ->assertSee('"playlistId":"PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG"', false)->assertSee('"playlistIndex":1', false);
    }

    public function test_uploaded_videos_and_materials(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('admin');
        $v1 = $this->upload($admin, 'video', '01_Introduction-au-tableur.mp4', $this->mp4());
        $v2 = $this->upload($admin, 'video', '02_Les-cellules.mp4', $this->mp4());
        $v3 = $this->upload($admin, 'video', '03_Formules.mp4', $this->mp4());
        $v4 = $this->upload($admin, 'video', '04_Graphiques.mp4', $this->mp4());
        $pdf = $this->upload($admin, 'document', 'Manuel Excel.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

        // A document that is not what it claims is refused.
        $this->actingAs($admin)->postJson(route('instructor.intro-video.chunk'), [
            'kind' => 'document', 'upload_id' => 'fakepdf12345', 'index' => 0, 'total' => 1, 'filename' => 'faux.pdf', 'size' => 5,
            'chunk' => UploadedFile::fake()->createWithContent('chunk', 'hello'),
        ])->assertStatus(422);

        $this->actingAs($admin)->post(route('instructor.courses.store'), $this->payload([
            'videos' => [['token' => $v1, 'name' => '01_Introduction-au-tableur.mp4'], ['token' => $v2, 'name' => '02_Les-cellules.mp4'],
                         ['token' => $v3, 'name' => '03_Formules.mp4'], ['token' => $v4, 'name' => '04_Graphiques.mp4']],
            'documents' => [['token' => $pdf, 'name' => 'Manuel Excel.pdf']],
        ]))->assertSessionHasNoErrors();

        $course = Course::first();
        $lessons = $course->modules()->with('lessons.translations')->get()->flatMap->lessons;
        // 3 planned lessons, 4 videos: one lesson added to the last module.
        $this->assertCount(4, $lessons);
        $this->assertTrue($lessons->every(fn ($l) => $l->videoKind() === 'upload' && $l->video_path));
        $this->assertSame('Introduction au tableur', $lessons[0]->title('fr'));

        $book = $course->books()->first();
        $this->assertSame(['Manuel Excel', 'pdf'], [$book->title, $book->kind()]);

        // An enrolled student keeps the material in their library (offline in the app).
        $course->update(['status' => 'published']);
        $student = $this->makeUser();
        $enrollment = $this->enroll($student, $course);
        $this->actingAs($student)->get(route('student.courses.player', $enrollment))->assertOk()->assertSee(__('learn.course_materials'));
        $this->actingAs($student)->postJson(route('student.library.add', $book))->assertOk();
        $this->actingAs($student)->getJson(route('student.library.data'))->assertJsonPath('items.0.title', 'Manuel Excel');
    }
}
