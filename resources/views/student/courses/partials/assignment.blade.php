@php $passed = $submission?->isPassed(); @endphp
<div class="border rounded-xl p-3 p-md-4 bg-light">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0"><x-icon name="clipboard-check" class="me-1" />{{ __('lms.your_work') }}</h5>
        <span class="small text-muted">{{ __('lms.pass_mark_is', ['score' => $lesson->assignment_pass_score]) }} / {{ $lesson->assignment_max_score }}</span>
    </div>

    @if($submission)
        <div class="alert {{ $submission->isGraded() ? ($passed ? 'alert-success' : 'alert-danger') : 'alert-info' }}">
            @if($submission->isGraded())
                <div class="fw-semibold">
                    {{ __('lms.your_grade') }} : {{ rtrim(rtrim((string) $submission->score, '0'), '.') }} / {{ $lesson->assignment_max_score }}
                    — {{ $passed ? __('lms.assignment_passed') : __('lms.assignment_failed') }}
                </div>
                @if($submission->feedback)
                    <div class="mt-2 small"><strong>{{ __('lms.feedback') }} :</strong><div style="white-space:pre-wrap">{{ $submission->feedback }}</div></div>
                @endif
            @else
                <x-icon name="hourglass-split" class="me-1" />{{ __('lms.assignment_waiting', ['date' => $submission->submitted_at?->isoFormat('L LT')]) }}
            @endif
        </div>
        @if($submission->content || $submission->file_path)
            <details class="mb-3">
                <summary class="small text-primary">{{ __('lms.view_my_submission') }}</summary>
                @if($submission->content)<div class="border rounded bg-white p-3 mt-2 small" style="white-space:pre-wrap">{{ $submission->content }}</div>@endif
                @if($submission->file_path)
                    <a href="{{ route('media.submission', $submission) }}" class="resource-link mt-2"><x-icon name="file-earmark" /><span>{{ $submission->original_name }}</span></a>
                @endif
            </details>
        @endif
    @endif

    @unless($passed)
        <form method="POST" action="{{ route('student.assignment.submit', $lesson) }}" enctype="multipart/form-data">
            @csrf
            <label class="form-label small fw-semibold" for="assignmentContent">{{ $submission ? __('lms.resubmit') : __('lms.your_answer') }}</label>
            <textarea name="content" id="assignmentContent" rows="7" class="form-control mb-2 @error('content') is-invalid @enderror" maxlength="50000">{{ old('content', $submission?->content) }}</textarea>
            @error('content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <label class="form-label small fw-semibold" for="assignmentFile">{{ __('lms.attach_file') }} <span class="text-muted fw-normal">({{ __('max. :size', ['size' => \App\Support\Format::bytes(config('lms.submission.max_size_mb') * 1048576)]) }} — {{ implode(', ', config('lms.submission.extensions')) }})</span></label>
            <input type="file" name="file" id="assignmentFile" class="form-control mb-3 @error('file') is-invalid @enderror">
            @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <button class="btn btn-primary"><x-icon name="send" class="me-1" />{{ __('lms.submit_assignment') }}</button>
        </form>
    @endunless
</div>
