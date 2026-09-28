{{-- A lesson resource. PDF, audio, video and images open in the in-app reader (also offline). --}}
@php $viewable = in_array(\App\Support\MediaKind::of($resource->original_name), ['pdf', 'audio', 'video', 'image'], true); @endphp
<div class="resource-link">
    <x-icon :name="$resource->icon()" />
    <span class="flex-grow-1 min-w-0"><span class="d-block fw-semibold text-truncate">{{ $resource->title }}</span><span class="small text-muted">{{ $resource->original_name }} · {{ $resource->sizeLabel() }}</span></span>
    @if($viewable)
        <button type="button" class="btn btn-sm btn-outline-primary" data-open-media="{{ route('media.resource', $resource) }}"
                data-media-kind="{{ \App\Support\MediaKind::of($resource->original_name) }}" data-media-title="{{ $resource->title }}">
            <x-icon name="eye" class="me-1" />{{ __('learn.open') }}</button>
    @endif
    <a href="{{ route('media.resource', $resource) }}?download=1" class="btn btn-sm btn-light" aria-label="{{ __('lms.download') }}"><x-icon name="download" /></a>
</div>
