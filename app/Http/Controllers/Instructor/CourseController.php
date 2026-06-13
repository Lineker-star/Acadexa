<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranslation;
use App\Models\Module;
use App\Models\ModuleTranslation;
use App\Models\CourseTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = $request->user()->courses()
            ->with(['translations', 'category.translations'])
            ->withCount(['enrollments', 'reviews'])
            ->latest()
            ->paginate(15);

        return view('instructor.courses.index', compact('courses'));
    }

    public function create()
    {
        $categories = Category::active()->with('translations')->orderBy('order')->get();
        return view('instructor.courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'level'       => ['required', 'in:beginner,intermediate,advanced'],
            'price'       => ['required', 'numeric', 'min:0'],
            'thumbnail'   => ['nullable', 'image', 'max:4096', 'mimes:jpeg,png,jpg,webp'],
        ]);

        $slug = Str::slug($data['title']) . '-' . Str::random(4);

        $thumbnailName = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailName = time() . '_' . $request->file('thumbnail')->getClientOriginalName();
            $request->file('thumbnail')->storeAs('thumbnails', $thumbnailName, 'public');
        }

        $course = Course::create([
            'instructor_id'   => $request->user()->id,
            'category_id'     => $data['category_id'],
            'level'           => $data['level'],
            'price'           => $data['price'],
            'status'          => 'draft',
            'slug'            => $slug,
            'thumbnail'       => $thumbnailName,
        ]);

        CourseTranslation::create([
            'course_id'   => $course->id,
            'locale'      => 'en',
            'title'       => $data['title'],
            'description' => $data['description'],
        ]);

        return redirect()->route('instructor.courses.edit', $course)
            ->with('success', __('messages.course_created'));
    }

    public function edit(Course $course)
    {
        $this->authorizeInstructor($course);

        $categories = Category::active()->with('translations')->orderBy('order')->get();
        $locales    = config('app.supported_locales', ['en', 'fr', 'es', 'pt', 'zh', 'ar']);
        $course->load(['translations', 'modules.lessons.translations', 'modules.translations']);

        return view('instructor.courses.edit', compact('course', 'categories', 'locales'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorizeInstructor($course);

        $data = $request->validate([
            'category_id'     => ['required', 'exists:categories,id'],
            'level'           => ['required', 'in:beginner,intermediate,advanced'],
            'price'           => ['required', 'numeric', 'min:0'],
            'thumbnail'       => ['nullable', 'image', 'max:4096', 'mimes:jpeg,png,jpg,webp'],
            'translations'    => ['required', 'array'],
            'translations.en.title' => ['required', 'string', 'max:255'],
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) Storage::disk('public')->delete('thumbnails/' . $course->thumbnail);
            $fn = time() . '_' . $request->file('thumbnail')->getClientOriginalName();
            $request->file('thumbnail')->storeAs('thumbnails', $fn, 'public');
            $data['thumbnail'] = $fn;
        }

        $course->update([
            'category_id' => $data['category_id'],
            'level'       => $data['level'],
            'price'       => $data['price'],
            'thumbnail'   => $data['thumbnail'] ?? $course->thumbnail,
        ]);

        foreach ($request->translations as $locale => $trans) {
            if (empty($trans['title'])) continue;
            CourseTranslation::updateOrCreate(
                ['course_id' => $course->id, 'locale' => $locale],
                [
                    'title'           => $trans['title'],
                    'description'     => $trans['description'] ?? '',
                    'requirements'    => $trans['requirements'] ?? '',
                    'what_you_learn'  => $trans['what_you_learn'] ?? '',
                ]
            );
        }

        // Recalculate duration
        $totalMinutes = $course->modules()->with('lessons')->get()
            ->flatMap->lessons->sum('duration_minutes');
        $course->update(['duration_minutes' => $totalMinutes]);

        return back()->with('success', __('messages.course_updated'));
    }

    public function destroy(Course $course)
    {
        $this->authorizeInstructor($course);
        abort_if(in_array($course->status, ['published']), 403, 'Cannot delete a published course.');
        $course->delete();
        return redirect()->route('instructor.courses.index')->with('success', __('messages.course_deleted'));
    }

    public function submit(Course $course)
    {
        $this->authorizeInstructor($course);
        abort_if($course->status !== 'draft', 403);
        $course->update(['status' => 'pending']);
        return back()->with('success', __('messages.course_submitted'));
    }

    public function storeModule(Request $request, Course $course)
    {
        $this->authorizeInstructor($course);
        $request->validate(['title' => ['required', 'string', 'max:255']]);

        $order  = $course->modules()->max('order') + 1;
        $module = Module::create(['course_id' => $course->id, 'order' => $order, 'title' => $request->title]);
        ModuleTranslation::create(['module_id' => $module->id, 'locale' => 'en', 'title' => $request->title]);

        return response()->json(['module' => $module, 'message' => 'Module created']);
    }

    public function storeLesson(Request $request, Module $module)
    {
        $this->authorizeInstructor($module->course);
        $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:video,text,quiz,assignment'],
            'video_url'        => ['nullable', 'string'],
            'content'          => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'is_free_preview'  => ['boolean'],
        ]);

        $order  = $module->lessons()->max('order') + 1;
        $lesson = Lesson::create([
            'module_id'        => $module->id,
            'order'            => $order,
            'type'             => $request->type,
            'video_url'        => $request->video_url,
            'content'          => $request->content,
            'duration_minutes' => $request->duration_minutes ?? 0,
            'is_free_preview'  => $request->boolean('is_free_preview'),
        ]);

        LessonTranslation::create(['lesson_id' => $lesson->id, 'locale' => 'en', 'title' => $request->title]);

        return response()->json(['lesson' => $lesson, 'message' => 'Lesson created']);
    }

    public function destroyModule(Module $module)
    {
        $this->authorizeInstructor($module->course);
        $module->delete();
        return response()->json(['message' => 'Module deleted']);
    }

    public function destroyLesson(Lesson $lesson)
    {
        $this->authorizeInstructor($lesson->module->course);
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted']);
    }

    private function authorizeInstructor(Course $course): void
    {
        $user = auth()->user();
        abort_if(
            $course->instructor_id !== $user->id && ! $user->isAdmin(),
            403,
            'Unauthorized.'
        );
    }
}
