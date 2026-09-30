{{-- Presentation video of a course: YouTube link OR uploaded file (max 20 MB, sent in 1 MB chunks).
     Used by the creation wizard ($course = null) and the "Information" tab of the course editor. --}}
@php
    $course ??= null;
    $hasFile = $course?->intro_video_path;
    $source = old('intro_source', $hasFile ? 'upload' : 'youtube');
    $maxMb = config('lms.video.max_size_mb');
@endphp
<div data-intro-video>
    <label class="form-label fw-semibold d-block">{{ __('learn.intro_video') }}</label>
    <div class="btn-group btn-group-sm mb-3" role="group">
        <input type="radio" class="btn-check" name="intro_source" id="introSrcYoutube" value="youtube" @checked($source === 'youtube')>
        <label class="btn btn-outline-primary" for="introSrcYoutube"><x-icon name="youtube" class="me-1" />{{ __('learn.intro_source_youtube') }}</label>
        <input type="radio" class="btn-check" name="intro_source" id="introSrcUpload" value="upload" @checked($source === 'upload')>
        <label class="btn btn-outline-primary" for="introSrcUpload"><x-icon name="cloud-arrow-up" class="me-1" />{{ __('learn.intro_source_upload', ['max' => $maxMb]) }}</label>
    </div>

    {{-- YouTube link --}}
    <div data-intro-panel="youtube" @if($source !== 'youtube') hidden @endif>
        <input type="text" name="intro_youtube_url" id="intro_youtube_url" class="form-control @error('intro_youtube_url') is-invalid @enderror" data-youtube-input
               value="{{ old('intro_youtube_url', $course?->intro_youtube_id ? 'https://www.youtube.com/watch?v=' . $course->intro_youtube_id : '') }}"
               placeholder="{{ __('https://www.youtube.com/watch?v=…') }}" aria-label="{{ __('learn.intro_source_youtube') }}">
        @error('intro_youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('learn.intro_video_help') }}</div>
        <div class="mt-2" style="max-width:520px" data-youtube-preview></div>
    </div>

    {{-- Uploaded file --}}
    <div data-intro-panel="upload" @if($source !== 'upload') hidden @endif
         data-intro-uploader
         data-url="{{ route('instructor.intro-video.chunk') }}"
         data-max-mb="{{ $maxMb }}"
         data-chunk-mb="{{ config('lms.video.chunk_size_mb') }}"
         data-extensions="{{ implode(',', config('lms.video.extensions')) }}">
        <input type="hidden" name="intro_video_token" value="">
        @if($hasFile)
            <div class="mb-2" data-intro-current>
                <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark mb-1" style="max-width:520px">
                    <video controls preload="metadata" src="{{ route('media.course.intro', $course) }}"></video>
                </div>
                <div class="form-check small">
                    <input class="form-check-input" type="checkbox" name="remove_intro_video" value="1" id="removeIntro">
                    <label class="form-check-label" for="removeIntro">{{ __('learn.intro_remove') }}</label>
                </div>
            </div>
        @endif
        <div class="dropzone" tabindex="0" role="button" aria-label="{{ __('learn.intro_source_upload', ['max' => $maxMb]) }}">
            <x-icon name="cloud-arrow-up" />
            <div class="fw-semibold mt-2">{{ $hasFile ? __('learn.intro_replace') : __('lms.drop_video') }}</div>
            <div class="small text-muted">{{ __('lms.video_formats', ['formats' => 'MP4, WebM, MOV', 'max' => \App\Support\Format::bytes($maxMb * 1048576)]) }}</div>
        </div>
        <input type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v,video/ogg,.mp4,.webm,.mov,.m4v,.ogv" hidden>
        <div class="mt-2" data-role="status"></div>
        <div class="mt-2" style="max-width:520px" data-role="preview"></div>
    </div>
</div>
