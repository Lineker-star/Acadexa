<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Lesson;
use App\Models\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LessonResourceController extends Controller
{
    use AuthorizesCourse;

    public function store(Request $request, Lesson $lesson)
    {
        $course = $lesson->module->course;
        $this->authorizeCourse($course);
        $this->ensureEditable($course);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'file'  => [
                'required', 'file',
                'max:' . config('lms.resource.max_size_mb') * 1024,
                'extensions:' . implode(',', config('lms.resource.extensions')),
            ],
        ]);

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $path = $file->storeAs(
            "resources/course_{$course->id}",
            Str::uuid() . '.' . strtolower($file->getClientOriginalExtension()),
            'local'
        );

        LessonResource::create([
            'lesson_id'     => $lesson->id,
            'title'         => $data['title'] ?: pathinfo($original, PATHINFO_FILENAME),
            'path'          => $path,
            'original_name' => Str::limit($original, 250, ''),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
            'order'         => (int) $lesson->resources()->max('order') + 1,
        ]);
        $lesson->touch();

        return redirect()->route('instructor.lessons.edit', $lesson)->withFragment('resources')
            ->with('success', __('lms.resource_added'));
    }

    public function destroy(LessonResource $resource)
    {
        $lesson = $resource->lesson;
        $this->authorizeCourse($lesson->module->course);
        $this->ensureEditable($lesson->module->course);

        Storage::disk('local')->delete($resource->path);
        $resource->delete();
        $lesson->touch();

        return redirect()->route('instructor.lessons.edit', $lesson)->withFragment('resources')
            ->with('success', __('lms.resource_deleted'));
    }
}
