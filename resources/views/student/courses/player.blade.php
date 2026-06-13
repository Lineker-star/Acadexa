@extends('layouts.app')
@section('title', $enrollment->course->title() . ' — Player')

@push('styles')
<style>
body { overflow: hidden; }
.player-topbar { background:var(--primary);padding:.6rem 1rem;display:flex;align-items:center;gap:1rem;position:fixed;top:0;left:0;right:0;z-index:200; }
.player-topbar a { color:rgba(255,255,255,.7);font-size:.9rem; }
.player-topbar .course-name { color:#fff;font-family:'Poppins',sans-serif;font-weight:600;font-size:.95rem; }
.player-wrap { margin-top:56px;height:calc(100vh - 56px);display:flex; }
</style>
@endpush

@section('content')
<!-- Player Topbar (replaces main navbar for immersive experience) -->
<div class="player-topbar">
    <a href="{{ route('student.courses.index') }}"><i class="bi bi-arrow-left"></i></a>
    <span class="course-name flex-grow-1">{{ $enrollment->course->title() }}</span>
    <div class="d-flex align-items-center gap-3">
        <div style="font-size:.8rem;color:rgba(255,255,255,.75);">
            <span id="progressPct">{{ $enrollment->progress_percent }}</span>% Complete
        </div>
        <div style="width:120px;height:6px;background:rgba(255,255,255,.2);border-radius:3px;">
            <div id="progressBar" style="width:{{ $enrollment->progress_percent }}%;height:100%;background:var(--secondary);border-radius:3px;transition:width .5s;"></div>
        </div>
    </div>
</div>

<div class="player-wrap">
    <!-- Sidebar: Module/Lesson List -->
    <div class="player-sidebar">
        <div style="padding:.75rem 1rem;font-family:'Poppins',sans-serif;font-weight:700;font-size:.9rem;border-bottom:1px solid var(--border);color:var(--primary);">
            Course Content
        </div>
        @foreach($enrollment->course->modules as $module)
        <div>
            <div class="module-header">{{ $module->title(app()->getLocale()) }}</div>
            @foreach($module->lessons as $lesson)
            <a href="{{ route('student.courses.player', $enrollment) }}?lesson={{ $lesson->id }}"
               class="lesson-item {{ $currentLesson?->id === $lesson->id ? 'active' : '' }} {{ in_array($lesson->id, $completedLessonIds) ? 'completed' : '' }}"
               onclick="loadLesson({{ $lesson->id }}, this); return false;">
                <span class="check-icon">
                    @if(in_array($lesson->id, $completedLessonIds))
                        <i class="bi bi-check-circle-fill text-success"></i>
                    @else
                        <i class="bi bi-{{ $lesson->type === 'video' ? 'play-circle' : ($lesson->type === 'quiz' ? 'patch-question' : 'file-text') }} text-muted"></i>
                    @endif
                </span>
                <div>
                    <div class="lesson-title">{{ $lesson->title(app()->getLocale()) }}</div>
                    @if($lesson->duration_minutes)
                    <div style="font-size:.75rem;color:var(--text-muted);">{{ $lesson->duration_minutes }}m</div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        @endforeach
    </div>

    <!-- Main Player Area -->
    <div class="player-main d-flex flex-column" id="playerMain">
        @if($currentLesson)
        <!-- Video / Content Area -->
        <div class="flex-grow-1 d-flex flex-column" id="lessonContent">
            @if($currentLesson->type === 'video')
            <div class="ratio ratio-16x9" style="max-height:55vh;background:#000;">
                @if(Str::contains($currentLesson->video_url, ['youtube.com', 'youtu.be']))
                    @php
                        preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $currentLesson->video_url, $m);
                        $ytId = $m[1] ?? '';
                    @endphp
                    <iframe src="https://www.youtube.com/embed/{{ $ytId }}"
                            title="{{ e($currentLesson->title()) }}" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                @elseif(Str::contains($currentLesson->video_url, 'vimeo.com'))
                    @php preg_match('/vimeo\.com\/(\d+)/', $currentLesson->video_url, $m); $vimeoId = $m[1] ?? ''; @endphp
                    <iframe src="https://player.vimeo.com/video/{{ $vimeoId }}"
                            title="{{ e($currentLesson->title()) }}" frameborder="0" allowfullscreen></iframe>
                @else
                    <video controls class="w-100 h-100">
                        <source src="{{ $currentLesson->video_url }}" type="video/mp4">
                    </video>
                @endif
            </div>
            @endif

            <!-- Lesson Text Content -->
            <div class="p-4 overflow-auto flex-grow-1" style="background:#fff;">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h3 class="h5">{{ $currentLesson->title(app()->getLocale()) }}</h3>
                    <button id="markCompleteBtn"
                            class="btn btn-sm {{ in_array($currentLesson->id, $completedLessonIds) ? 'btn-success' : 'btn-primary' }}"
                            data-lesson-id="{{ $currentLesson->id }}"
                            {{ in_array($currentLesson->id, $completedLessonIds) ? 'disabled' : '' }}>
                        {{ in_array($currentLesson->id, $completedLessonIds) ? '✓ Completed' : 'Mark as Complete' }}
                    </button>
                </div>

                @if($currentLesson->type === 'text' || $currentLesson->content)
                <div class="lesson-text-content" style="max-width:760px;">
                    {!! nl2br(e($currentLesson->content ?? '')) !!}
                </div>
                @endif

                @if($currentLesson->type === 'quiz' && $currentLesson->quiz)
                @include('student.courses.partials.quiz', ['quiz' => $currentLesson->quiz, 'lessonId' => $currentLesson->id])
                @endif

                @if($currentLesson->attachment_path)
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="bi bi-paperclip me-2"></i>Attachment</h6>
                    <a href="{{ asset('storage/' . $currentLesson->attachment_path) }}"
                       class="btn btn-outline-primary btn-sm" download>
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                </div>
                @endif

                <!-- Q&A Comments -->
                <div class="mt-5 pt-4 border-top">
                    <h5 class="mb-4">Q&A / Comments ({{ $currentLesson->comments->count() }})</h5>
                    <form method="POST" action="{{ route('student.lesson.comment', $currentLesson) }}" class="mb-4">
                        @csrf
                        <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Ask a question..."></textarea>
                        <button class="btn btn-primary btn-sm">Post</button>
                    </form>
                    @foreach($currentLesson->comments as $comment)
                    <div class="d-flex gap-3 mb-4">
                        <img src="{{ $comment->user->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="commenter">
                        <div class="flex-grow-1">
                            <strong style="font-size:.88rem;">{{ $comment->user->name }}</strong>
                            <span class="text-muted ms-2" style="font-size:.78rem;">{{ $comment->created_at->diffForHumans() }}</span>
                            <p style="font-size:.9rem;margin:.25rem 0;">{{ $comment->comment }}</p>
                            @foreach($comment->replies as $reply)
                            <div class="d-flex gap-2 mt-2 ps-3 border-start" style="border-color:var(--secondary)!important;">
                                <img src="{{ $reply->user->avatarUrl() }}" class="rounded-circle" width="28" height="28" alt="reply">
                                <div>
                                    <strong style="font-size:.8rem;">{{ $reply->user->name }}</strong>
                                    <p style="font-size:.85rem;margin:.1rem 0;">{{ $reply->comment }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @else
        <div class="d-flex align-items-center justify-content-center h-100 text-white">
            <div class="text-center">
                <div style="font-size:4rem;">🎬</div>
                <h4 class="mt-3">Select a lesson to begin</h4>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function loadLesson(lessonId, el) {
    // Update URL without reload (SPA-like behavior)
    const url = new URL(window.location);
    url.searchParams.set('lesson', lessonId);
    window.history.pushState({}, '', url);
    // Full reload to properly load content (can be enhanced to AJAX later)
    window.location.href = url.toString();
}
</script>
@endpush
