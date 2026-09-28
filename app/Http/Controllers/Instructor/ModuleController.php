<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Course;
use App\Models\Module;
use App\Models\ModuleTranslation;
use App\Services\ChunkedVideoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    use AuthorizesCourse;

    public function store(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $this->ensureEditable($course);
        $data = $this->validated($request, $course);

        $module = DB::transaction(function () use ($course, $data) {
            $module = Module::create([
                'course_id'      => $course->id,
                'order'          => (int) $course->modules()->max('order') + 1,
                'title'          => $data['translations'][$course->language]['title'],
                'duration_hours' => $data['duration_hours'],
            ]);
            $this->saveTranslations($module, $data['translations']);
            return $module;
        });

        return $this->respond($request, $course, __('lms.module_created'), ['module_id' => $module->id]);
    }

    public function update(Request $request, Module $module)
    {
        $course = $module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);
        $data = $this->validated($request, $course);

        DB::transaction(function () use ($module, $course, $data) {
            $module->update([
                'title'          => $data['translations'][$course->language]['title'],
                'duration_hours' => $data['duration_hours'],
            ]);
            $this->saveTranslations($module, $data['translations']);
        });

        return $this->respond($request, $course, __('lms.module_updated'));
    }

    public function destroy(Request $request, Module $module)
    {
        $course = $module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $uploads = app(ChunkedVideoUpload::class);
        foreach ($module->lessons()->with('resources')->get() as $lesson) {
            $uploads->deleteVideo($lesson);
            foreach ($lesson->resources as $resource) {
                Storage::disk('local')->delete($resource->path);
            }
        }
        $module->delete();
        $course->recalculateDuration();

        return $this->respond($request, $course, __('lms.module_deleted'));
    }

    /** Saves the order of modules after drag & drop: { ids: [3, 1, 2] }. */
    public function reorder(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $this->ensureEditable($course);
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $owned = $course->modules()->pluck('id')->all();
        abort_if(array_diff($ids, $owned), 422);

        DB::transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                Module::whereKey($id)->update(['order' => $position + 1]);
            }
        });

        return response()->json(['message' => __('lms.order_saved')]);
    }

    private function validated(Request $request, Course $course): array
    {
        $data = $request->validate([
            'duration_hours'             => ['required', 'numeric', 'min:0.5', 'max:1000'],
            'translations'               => ['required', 'array'],
            'translations.*.title'       => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:5000'],
        ]);
        $request->validate(
            ["translations.{$course->language}.title" => ['required', 'string', 'max:255']],
            [],
            ["translations.{$course->language}.title" => __('lms.module_title')]
        );

        return $data;
    }

    private function saveTranslations(Module $module, array $translations): void
    {
        foreach (CourseController::CONTENT_LOCALES as $locale) {
            $trans = $translations[$locale] ?? [];
            if (blank($trans['title'] ?? null)) {
                ModuleTranslation::where('module_id', $module->id)->where('locale', $locale)->delete();
                continue;
            }
            ModuleTranslation::updateOrCreate(
                ['module_id' => $module->id, 'locale' => $locale],
                ['title' => $trans['title'], 'description' => $trans['description'] ?? null]
            );
        }
    }

    private function respond(Request $request, Course $course, string $message, array $extra = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message] + $extra);
        }
        return redirect()->route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum'])
            ->with('success', $message);
    }
}
