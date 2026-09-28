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
