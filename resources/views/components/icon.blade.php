@props(['name', 'label' => null])
{{-- Inline SVG icon from resources/icons/sprite.svg. Size follows the font size (1em). --}}
<svg {{ $attributes->class(['icon']) }} width="1em" height="1em" fill="currentColor" @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif><use href="{{ \App\Support\Icons::url() }}#{{ \App\Support\Icons::normalize($name) }}"/></svg>
