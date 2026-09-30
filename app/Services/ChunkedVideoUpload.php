<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Resumable chunked upload of lesson videos to the private disk.
 * Chunks live in storage/app/private/chunks/{user}_{uploadId}/{index}.part
 * until every chunk is present, then they are concatenated in order.
 */
class ChunkedVideoUpload
{
    public function chunkDir(int $userId, string $uploadId): string
    {
        return Storage::disk('local')->path("chunks/{$userId}_{$uploadId}");
    }

    /** Indexes already received, so the browser can resume an interrupted upload. */
    public function receivedChunks(int $userId, string $uploadId): array
    {
        $dir = $this->chunkDir($userId, $uploadId);
        if (! is_dir($dir)) {
            return [];
        }
        $indexes = array_map(fn ($f) => (int) basename($f, '.part'), glob($dir . DIRECTORY_SEPARATOR . '*.part') ?: []);
        sort($indexes);
        return $indexes;
    }

    public function storeChunk(int $userId, string $uploadId, int $index, UploadedFile $chunk): void
    {
        $dir = $this->chunkDir($userId, $uploadId);
        File::ensureDirectoryExists($dir);
        $chunk->move($dir, $index . '.part');
    }

    public function isComplete(int $userId, string $uploadId, int $total): bool
    {
        return count($this->receivedChunks($userId, $uploadId)) >= $total;
    }

    /**
     * Concatenates the chunks, validates the result is a real video and attaches it to the lesson.
     */
    public function finalize(Lesson $lesson, int $userId, string $uploadId, int $total, string $originalName): Lesson
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $relative = "videos/course_{$lesson->module->course_id}/" . Str::uuid() . '.' . $ext;
        $video = $this->assemble($userId, $uploadId, $total, $originalName, $relative);

        $this->deleteVideo($lesson);

        $lesson->update([
            'video_source'        => Lesson::VIDEO_UPLOAD,
            'video_path'          => $video['path'],
            'video_original_name' => Str::limit($originalName, 250, ''),
            'video_size'          => $video['size'],
            'video_mime'          => $video['mime'],
            'video_url'           => null,
            'youtube_id'          => null,
            'youtube_playlist_id' => null,
        ]);

        return $lesson;
    }

    /** Joins the chunks into $relative (private disk) and returns the absolute path. */
    public function join(int $userId, string $uploadId, int $total, string $relative): string
    {
        $dir = $this->chunkDir($userId, $uploadId);
        $target = Storage::disk('local')->path($relative);
        File::ensureDirectoryExists(dirname($target));

        $out = fopen($target, 'wb');
        try {
            for ($i = 0; $i < $total; $i++) {
                $part = $dir . DIRECTORY_SEPARATOR . $i . '.part';
                if (! is_file($part)) {
                    throw ValidationException::withMessages(['file' => __('lms.upload_missing_chunk', ['index' => $i])]);
                }
                $in = fopen($part, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } catch (\Throwable $e) {
            fclose($out);
            @unlink($target);
            throw $e;
        }
        fclose($out);
        File::deleteDirectory($dir);

        return $target;
    }

    /**
     * Joins the chunks into $relative (private disk) and checks the result is a real video.
     *
     * @return array{path: string, mime: string, size: int}
     */
    public function assemble(int $userId, string $uploadId, int $total, string $originalName, string $relative): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $target = $this->join($userId, $uploadId, $total, $relative);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($target) ?: 'application/octet-stream';
        // Some MP4 variants are reported as application/octet-stream; trust the extension only for those.
        $isVideo = in_array($mime, config('lms.video.mimes'), true)
            || ($mime === 'application/octet-stream' && in_array($ext, ['mp4', 'm4v'], true));
        if (! $isVideo) {
            @unlink($target);
            throw ValidationException::withMessages(['file' => __('lms.upload_not_video')]);
        }
        if ($mime === 'application/octet-stream' || $mime === 'video/x-m4v') {
            $mime = 'video/mp4';
        }

        return ['path' => $relative, 'mime' => $mime, 'size' => filesize($target)];
    }

    public function deleteVideo(Lesson $lesson): void
    {
        if ($lesson->video_path) {
            Storage::disk('local')->delete($lesson->video_path);
        }
    }

    /** Removes abandoned chunk folders (scheduled daily). */
    public function purgeStale(int $olderThanHours = 24): int
    {
        // Presentation videos uploaded in the creation wizard but never attached to a course.
        foreach (Storage::disk('local')->files('videos/intro_tmp') as $file) {
            if (Storage::disk('local')->lastModified($file) < time() - $olderThanHours * 3600) {
                Storage::disk('local')->delete($file);
            }
        }

        $root = Storage::disk('local')->path('chunks');
        if (! is_dir($root)) {
            return 0;
        }
        $removed = 0;
        foreach (glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - $olderThanHours * 3600) {
                File::deleteDirectory($dir);
                $removed++;
            }
        }
        return $removed;
    }
}
