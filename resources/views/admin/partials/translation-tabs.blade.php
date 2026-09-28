{{--
    Tabs with one set of fields per supported language.
    @param string $id          unique prefix for tab ids
    @param array  $fields      [name => ['label' => ..., 'type' => 'text'|'textarea', 'rows' => int]]
    @param callable $value     fn(string $locale, string $field): ?string   current value
    English is required; other languages are optional and fall back to English.
--}}
@php $localeNames = config('app.locale_names'); @endphp
<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach(config('app.supported_locales') as $loc)
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $id }}-{{ $loc }}" role="tab">
                {{ $localeNames[$loc] }} @if($loc === 'en')<span class="text-danger">*</span>@endif
                @if(filled($value($loc, array_key_first($fields))))<x-icon name="check-circle-fill" class="text-success ms-1" />@endif
            </button>
        </li>
    @endforeach
</ul>
<div class="tab-content mb-3">
    @foreach(config('app.supported_locales') as $loc)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $id }}-{{ $loc }}" role="tabpanel" dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ $loc }}">
            @if($loc !== 'en')
                <p class="small text-muted"><x-icon name="info-circle" class="me-1" />{{ __('Optional: leave empty to show the English version in this language.') }}</p>
            @endif
            @foreach($fields as $name => $field)
                <div class="mb-3">
                    <label class="form-label fw-bold" for="{{ $id }}-{{ $loc }}-{{ $name }}">{{ $field['label'] }} @if($loc === 'en')*@endif</label>
                    @if(($field['type'] ?? 'text') === 'textarea')
                        <textarea id="{{ $id }}-{{ $loc }}-{{ $name }}" name="translations[{{ $loc }}][{{ $name }}]" rows="{{ $field['rows'] ?? 6 }}"
                                  class="form-control @error("translations.$loc.$name") is-invalid @enderror" @required($loc === 'en')>{{ old("translations.$loc.$name", $value($loc, $name)) }}</textarea>
                    @else
                        <input type="text" id="{{ $id }}-{{ $loc }}-{{ $name }}" name="translations[{{ $loc }}][{{ $name }}]" maxlength="255"
                               class="form-control @error("translations.$loc.$name") is-invalid @enderror" value="{{ old("translations.$loc.$name", $value($loc, $name)) }}" @required($loc === 'en')>
                    @endif
                    @error("translations.$loc.$name")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    @endforeach
</div>
