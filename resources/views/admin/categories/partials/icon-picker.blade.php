{{-- Radio grid of the SVG icons allowed for categories (config lms.category_icons). --}}
<div class="d-flex flex-wrap gap-1" role="radiogroup" aria-label="{{ __('Icon') }}">
    @foreach(config('lms.category_icons') as $icon)
        <input type="radio" class="btn-check" name="icon" id="icon-{{ $field }}-{{ $icon }}" value="{{ $icon }}" @checked($selected === $icon)>
        <label class="btn btn-outline-secondary btn-sm px-2" for="icon-{{ $field }}-{{ $icon }}">
            <x-icon :name="$icon" style="font-size:1.1rem" />
        </label>
    @endforeach
</div>
