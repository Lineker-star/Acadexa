<?php

namespace App\Support;

/** Which in-app reader can open a file: pdf, audio, video, image — or null (download only). */
class MediaKind
{
    public static function of(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match (true) {
            $ext === 'pdf'                                        => 'pdf',
            in_array($ext, ['mp3', 'wav', 'm4a', 'ogg', 'oga'])   => 'audio',
            in_array($ext, ['mp4', 'webm', 'm4v', 'ogv', 'mov'])  => 'video',
            in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) => 'image',
            default                                               => null,
        };
    }
}
