{{-- Several files sent in 1 MB chunks (course creation). Each finished file adds hidden inputs
     {{ $field }}[n][token] and {{ $field }}[n][name]; the order of the list is kept. --}}
<div data-multi-upload
     data-kind="{{ $kind }}"
     data-field="{{ $field }}"
     data-url="{{ route('instructor.intro-video.chunk') }}"
     data-max-mb="{{ $maxMb }}"
     data-chunk-mb="{{ config('lms.video.chunk_size_mb') }}"
     data-extensions="{{ implode(',', $extensions) }}">
    <div class="dropzone py-3" tabindex="0" role="button" aria-label="{{ $help }}">
        <x-icon name="cloud-arrow-up" />
        <div class="small fw-semibold mt-1">{{ __('learn.drop_files') }}</div>
        <div class="small text-muted">{{ strtoupper(implode(', ', $extensions)) }} — {{ __('learn.max_per_file', ['max' => \App\Support\Format::bytes($maxMb * 1048576)]) }}</div>
    </div>
    <input type="file" accept="{{ $accept }}" multiple hidden>
    <div class="form-text">{{ $help }}</div>
    <ol class="upload-list list-unstyled mb-0 mt-2" data-role="list"></ol>
</div>
