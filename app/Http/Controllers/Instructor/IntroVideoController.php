<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\ChunkedVideoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Upload of a course presentation video (max 20 MB), in 1 MB chunks so that it works whatever
 * the PHP upload limits of the host. It can start before the course exists (creation wizard):
 * the assembled file waits in a temporary folder and is attached when the course is saved,
 * with the token returned here.
 */
class IntroVideoController extends Controller
{
    public const TMP_DIR = 'videos/intro_tmp';

    public function __construct(private ChunkedVideoUpload $uploads) {}

    public function chunk(Request $request)
    {
        $maxBytes = config('lms.video.max_size_mb') * 1024 * 1024;
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'index'     => ['required', 'integer', 'min:0'],
            'total'     => ['required', 'integer', 'min:1', 'max:1000'],
            'filename'  => ['required', 'string', 'max:255'],
            'size'      => ['required', 'integer', 'min:1', 'max:' . $maxBytes],
            'chunk'     => ['required', 'file', 'max:' . (config('lms.video.chunk_size_mb') + 1) * 1024],
        ], [
            'size.max' => __('lms.upload_too_large', ['max' => config('lms.video.max_size_mb')]),
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        abort_unless(in_array($ext, config('lms.video.extensions'), true), 422, __('lms.upload_bad_extension'));
        abort_if($data['index'] >= $data['total'], 422);

        $userId = $request->user()->id;
        $this->uploads->storeChunk($userId, $data['upload_id'], $data['index'], $request->file('chunk'));
        if (! $this->uploads->isComplete($userId, $data['upload_id'], $data['total'])) {
            return response()->json(['done' => false]);
        }

        $token = $userId . '-' . $data['upload_id'];
        $video = $this->uploads->assemble($userId, $data['upload_id'], $data['total'], $data['filename'], self::TMP_DIR . "/{$token}.{$ext}");

        return response()->json([
            'done'  => true,
            'token' => $token,
            'size'  => \App\Support\Format::bytes($video['size']),
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
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'video/mp4';
                return ['path' => $file, 'mime' => $mime === 'application/octet-stream' ? 'video/mp4' : $mime, 'size' => filesize($absolute)];
            }
        }
        return null;
    }
}
