<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\ChunkedVideoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Uploads made while creating or editing a course, in 1 MB chunks so that they work whatever the
 * PHP upload limits of the host: videos (max 20 MB each) and course materials (PDF, PowerPoint).
 * They can start before the course exists (creation wizard): the assembled file waits in a
 * temporary folder and is attached when the course is saved, with the token returned here.
 */
class IntroVideoController extends Controller
{
    public const TMP_DIR = 'videos/intro_tmp';

    public function __construct(private ChunkedVideoUpload $uploads) {}

    public function chunk(Request $request)
    {
        $document = $request->input('kind') === 'document';
        $limits = $document ? config('lms.document') : config('lms.video');
        $maxBytes = $limits['max_size_mb'] * 1024 * 1024;
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'index'     => ['required', 'integer', 'min:0'],
            'total'     => ['required', 'integer', 'min:1', 'max:1000'],
            'filename'  => ['required', 'string', 'max:255'],
            'size'      => ['required', 'integer', 'min:1', 'max:' . $maxBytes],
            'chunk'     => ['required', 'file', 'max:' . (config('lms.video.chunk_size_mb') + 1) * 1024],
        ], [
            'size.max' => __('lms.upload_too_large', ['max' => $limits['max_size_mb']]),
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        abort_unless(in_array($ext, $limits['extensions'], true), 422, $document ? __('learn.document_bad_extension') : __('lms.upload_bad_extension'));
        abort_if($data['index'] >= $data['total'], 422);

        $userId = $request->user()->id;
        $this->uploads->storeChunk($userId, $data['upload_id'], $data['index'], $request->file('chunk'));
        if (! $this->uploads->isComplete($userId, $data['upload_id'], $data['total'])) {
            return response()->json(['done' => false]);
        }

        $token = $userId . '-' . $data['upload_id'];
        $relative = self::TMP_DIR . "/{$token}.{$ext}";

        if ($document) {
            $path = $this->uploads->join($userId, $data['upload_id'], $data['total'], $relative);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
            // A PDF must really be a PDF; PowerPoint files are reported under several types.
            $valid = $ext === 'pdf' ? $mime === 'application/pdf' : in_array($mime, config('lms.document.mimes'), true);
            if (! $valid) {
                Storage::disk('local')->delete($relative);
                return response()->json(['message' => __('learn.document_bad_extension')], 422);
            }
            $size = filesize($path);
        } else {
            $size = $this->uploads->assemble($userId, $data['upload_id'], $data['total'], $data['filename'], $relative)['size'];
        }

        return response()->json([
            'done'  => true,
            'token' => $token,
            'size'  => \App\Support\Format::bytes($size),
        ]);
    }

    /**
     * Path of a finished temporary upload that belongs to this user, or null.
     *
     * @return array{path: string, mime: string, size: int}|null
     */
    public static function pending(int $userId, ?string $token): ?array
    {
        if (! $token || ! preg_match('/^' . $userId . '-[A-Za-z0-9_-]{8,64}$/', $token)) {
            return null;
        }
        foreach (Storage::disk('local')->files(self::TMP_DIR) as $file) {
            if (pathinfo($file, PATHINFO_FILENAME) === $token) {
                $absolute = Storage::disk('local')->path($file);
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'application/octet-stream';
                $mime = match (true) {
                    $ext === 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    $ext === 'ppt'  => 'application/vnd.ms-powerpoint',
                    $mime === 'application/octet-stream' => 'video/mp4',
                    default => $mime,
                };
                return ['path' => $file, 'mime' => $mime, 'size' => filesize($absolute), 'ext' => $ext];
            }
        }
        return null;
    }
}
