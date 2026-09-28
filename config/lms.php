<?php

return [

    /*
    | Uploaded lesson videos are sent in chunks so that files far larger than
    | PHP's upload_max_filesize (40 MB on the current hosting) can be uploaded,
    | and an interrupted upload resumes where it stopped.
    */
    'video' => [
        'max_size_mb'   => (int) env('LMS_VIDEO_MAX_MB', 4096),
        'chunk_size_mb' => (int) env('LMS_VIDEO_CHUNK_MB', 8),
        'mimes'         => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-m4v'],
        'extensions'    => ['mp4', 'webm', 'ogv', 'mov', 'm4v'],
    ],

    'resource' => [
        'max_size_mb' => (int) env('LMS_RESOURCE_MAX_MB', 35),
        'extensions'  => ['pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp',
                          'txt', 'zip', 'rar', '7z', 'png', 'jpg', 'jpeg', 'webp', 'mp3', 'wav', 'm4a'],
    ],

    'submission' => [
        'max_size_mb' => (int) env('LMS_SUBMISSION_MAX_MB', 25),
        'extensions'  => ['pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'png', 'jpg', 'jpeg'],
    ],

    /*
    | Assessment path: a quiz after every lesson, an exercise after every module and a
    | final evaluation after the course. The final evaluation is also taken as a
    | placement test (diagnostic) and retaken later to measure knowledge over time.
    */
    'assessment' => [
        'pass_percent'  => 70,                                        // 7/10 — instructors may only raise it
        'min_questions' => ['lesson' => 10, 'module' => 10, 'course' => 20],
        'retake_cooldown_days' => (int) env('LMS_RETAKE_COOLDOWN_DAYS', 7),
        'reassess_after_days'  => (int) env('LMS_REASSESS_AFTER_DAYS', 30),
        // Change (in points) between two evaluations that counts as progression / regression.
        'trend_threshold' => 5,
    ],

    // Course books kept in the student's account library and readable offline in the app.
    'book' => [
        'max_size_mb' => (int) env('LMS_BOOK_MAX_MB', 200),
        'extensions'  => ['pdf', 'mp3', 'm4a', 'ogg', 'wav', 'mp4', 'webm'],
    ],

    // A video lesson can be marked complete once this share of it has been watched.
    'video_completion_ratio' => 0.9,

    // SVG icons (Bootstrap Icons names) offered for categories in the admin.
    'category_icons' => [
        'bi-laptop', 'bi-code-slash', 'bi-globe2', 'bi-phone', 'bi-bar-chart-line', 'bi-shield-lock', 'bi-cpu',
        'bi-briefcase', 'bi-rocket-takeoff', 'bi-cash-coin', 'bi-kanban', 'bi-graph-up-arrow', 'bi-bank',
        'bi-palette', 'bi-vector-pen', 'bi-camera', 'bi-music-note-beamed', 'bi-film', 'bi-translate',
        'bi-chat-left-text', 'bi-chat-right-text', 'bi-heart-pulse', 'bi-capsule', 'bi-tree', 'bi-flower1',
        'bi-book', 'bi-mortarboard', 'bi-journal-bookmark', 'bi-stars', 'bi-lightbulb', 'bi-people',
        'bi-building', 'bi-tools', 'bi-calculator', 'bi-eyedropper', 'bi-dribbble', 'bi-folder',
    ],

    // Days before the end of the trial at which the reminder e-mail is sent.
    'trial_reminder_days' => [3, 1],
];
