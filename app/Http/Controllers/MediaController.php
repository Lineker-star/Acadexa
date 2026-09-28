<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\Book;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Services\LessonAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Private media never lives under public/: every file goes through an access check.
 * Videos are served with HTTP Range support (BinaryFileResponse) so the player can
 * seek and the service worker can download them for offline use.
 */
class MediaController extends Controller
{
    public function __construct(private LessonAccess $access) {}

    public function video(Request $request, Lesson $lesson): BinaryFileResponse
    {
        abort_unless($lesson->video_path && $this->access->canView($request->user(), $lesson), 403);

        $path = Storage::disk('local')->path($lesson->video_path);
        abort_unless(is_file($path), 404);

        $response = new BinaryFileResponse($path, 200, [
            'Content-Type'  => $lesson->video_mime ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], false, ResponseHeaderBag::DISPOSITION_INLINE, true, false);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'video.' . pathinfo($path, PATHINFO_EXTENSION));

        return $response;
    }

    /**
     * Lesson resources open inline (in-app reader for PDF, audio, video, images);
     * ?download=1 saves the file instead.
     */
    public function resource(Request $request, LessonResource $resource)
    {
        abort_unless($this->access->canView($request->user(), $resource->lesson), 403);
        abort_unless(Storage::disk('local')->exists($resource->path), 404);

        return $this->serve(Storage::disk('local')->path($resource->path), $resource->original_name, $resource->mime, $request->boolean('download'));
    }

    /**
     * Course books are only read inside the app (and kept offline in the app's private
     * storage): they are always served inline, never as a download.
     */
    public function book(Request $request, Book $book): BinaryFileResponse
    {
        abort_unless($this->access->canViewBook($request->user(), $book), 403);
        $path = Storage::disk('local')->path($book->path);
        abort_unless(is_file($path), 404);

        return $this->serve($path, $book->original_name, $book->mime, false);
    }

    /** Range-capable response (seeking in audio/video, progressive PDF loading). */
    private function serve(string $path, string $name, ?string $mime, bool $download): BinaryFileResponse
    {
        $response = new BinaryFileResponse($path, 200, [
            'Content-Type'  => $mime ?: (\Illuminate\Support\Facades\File::mimeType($path) ?: 'application/octet-stream'),
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], false, null, true, false);
        $response->setContentDisposition(
            $download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $name,
            \Illuminate\Support\Str::ascii($name) ?: 'file'
        );

        return $response;
    }

    public function submission(Request $request, AssignmentSubmission $submission)
    {
        $user = $request->user();
        $course = $submission->lesson->module->course;
        abort_unless(
            $user && ($submission->user_id === $user->id || $user->isAdmin() || $course->instructor_id === $user->id),
            403
        );
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->original_name);
    }
}
