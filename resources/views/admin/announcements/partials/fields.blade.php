@include('admin.partials.translation-tabs', [
    'id' => 'ann',
    'fields' => [
        'title' => ['label' => __('Title')],
        'body'  => ['label' => __('Content'), 'type' => 'textarea', 'rows' => 6],
    ],
    'value' => fn ($loc, $field) => $announcement?->translations->firstWhere('locale', $loc)?->{$field}
        ?? ($loc === 'en' ? $announcement?->{$field} : null),
])

<div class="mb-4" style="max-width:320px">
    <label class="form-label fw-bold" for="audience">{{ __('Audience *') }}</label>
    <select name="audience" id="audience" class="form-select" required>
        @foreach(['all', 'students', 'instructors'] as $audience)
            <option value="{{ $audience }}" @selected(old('audience', $announcement?->audience ?? 'all') === $audience)>{{ __('lms.audience_' . $audience) }}</option>
        @endforeach
    </select>
</div>
@unless($announcement)
<div class="form-check form-switch mb-4">
    <input type="hidden" name="notify" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="notify" value="1" id="notifyAudience" @checked(old('notify', '1') === '1')>
    <label class="form-check-label" for="notifyAudience">{{ __('learn.announcement_notify') }}</label>
    <div class="form-text">{{ __('learn.announcement_notify_help') }}</div>
</div>
@endunless
