<nav class="lesson-nav" aria-label="{{ __('lms.lesson_navigation') }}">
    @if($prevStep)
        <a href="{{ $stepUrl($prevStep) }}" class="btn btn-outline-secondary"><x-icon name="chevron-left" class="me-1" />{{ __('lms.previous') }}</a>
    @else <span></span> @endif
    @if($nextStep)
        <a href="{{ $stepUrl($nextStep) }}" id="nextLessonBtn" class="btn btn-primary {{ $nextOpen ? '' : 'disabled' }}"
           @unless($nextOpen) title="{{ $nextStep['type'] === 'lesson' ? __('lms.lesson_locked') : __('learn.assessment_locked') }}" @endunless>
            {{ $nextStep['type'] === 'lesson' ? __('lms.next') : $stepTitle($nextStep) }}<x-icon name="chevron-right" class="ms-1" /></a>
    @elseif($enrollment->progress_percent >= 100)
        <a href="{{ route('student.certificates.index') }}" class="btn btn-success"><x-icon name="award" class="me-1" />{{ __('lms.get_certificate') }}</a>
    @endif
</nav>
