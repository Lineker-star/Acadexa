@php
    $isDone = in_array($s['key'], $doneKeys, true);
    $isOpen = $unlocked[$s['key']] ?? false;
    $isCurrent = $currentKey === $s['key'];
    $stepIcon = $s['type'] === 'lesson' ? $s['lesson']->icon() : $s['quiz']->icon();
@endphp
@if($isOpen)
    <a href="{{ $stepUrl($s) }}" class="outline-lesson {{ $isCurrent ? 'active' : '' }} {{ $s['type'] !== 'lesson' ? 'outline-assessment' : '' }}" @if($isCurrent) aria-current="page" @endif>
@else
    <span class="outline-lesson locked {{ $s['type'] !== 'lesson' ? 'outline-assessment' : '' }}" title="{{ $s['type'] === 'lesson' ? __('lms.lesson_locked') : __('learn.assessment_locked') }}">
@endif
    <span class="state">
        @if($isDone)<x-icon name="check-circle-fill" class="text-success" />
        @elseif(! $isOpen)<x-icon name="lock" />
        @else<x-icon name="circle" class="text-muted" />@endif
    </span>
    <span class="flex-grow-1">
        {{ $stepTitle($s) }}
        <span class="d-block meta">
            <x-icon :name="$stepIcon" class="me-1" />
            @if($s['type'] === 'lesson')
                {{ __('lms.type_' . $s['lesson']->type) }}@if($s['lesson']->duration_minutes) · {{ __('lms.minutes_short', ['count' => $s['lesson']->duration_minutes]) }}@endif
                @if($s['lesson']->requiresQuiz()) · {{ __('learn.with_quiz') }}@endif
            @else
                {{ trans_choice('lms.questions_count', $s['quiz']->questionCount(), ['count' => $s['quiz']->questionCount()]) }}
            @endif
        </span>
    </span>
@if($isOpen)</a>@else</span>@endif
