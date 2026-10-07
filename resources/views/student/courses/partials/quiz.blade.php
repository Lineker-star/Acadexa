@php
    $mode ??= 'standard';
    $standard = $mode === 'standard';
    $questions = $quiz->shuffle_questions ? $quiz->questions->shuffle() : $quiz->questions;
    $noAttemptsLeft = $standard && $state['attempts_left'] === 0;
    $passScore = $quiz->effectivePassingScore();
    $isOpen = $quiz->usesOpenQuestions() && $questions->contains(fn ($q) => $q->isOpen());
    // Open exercise already handed in: show the student's answers next to the detailed answers.
    $openReview = $isOpen && $standard && $state['passed'] && $state['last'] ? (array) $state['last']->answers : null;
@endphp
<div id="quizContainer" class="border rounded-xl p-3 p-md-4 bg-light"
     data-quiz-id="{{ $quiz->id }}"
     data-mode="{{ $mode }}"
     data-scope="{{ $quiz->scope }}"
     data-attempt-url="{{ route('student.quiz.attempt', $quiz) }}"
     data-start-url="{{ route('student.quiz.start', $quiz) }}"
     data-time-limit="{{ $quiz->time_limit_minutes ?? '' }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0"><x-icon :name="$quiz->icon()" class="me-1" />{{ trans_choice('lms.questions_count', $questions->count(), ['count' => $questions->count()]) }}</h5>
        <div class="small text-muted d-flex flex-wrap gap-3">
            @if($isOpen)<span><x-icon name="pencil-square" class="me-1" />{{ __('learn.exercise_open_hint') }}</span>
            @elseif($standard)<span><x-icon name="bullseye" class="me-1" />{{ $quiz->gradeOutOf() ? __('learn.pass_grade', ['grade' => $passScore * $quiz->gradeOutOf() / 100, 'out_of' => $quiz->gradeOutOf()]) : __('lms.pass_at', ['score' => $passScore]) }}</span>@endif
            @if($quiz->time_limit_minutes)<span><x-icon name="stopwatch" class="me-1" />{{ __('lms.minutes_short', ['count' => $quiz->time_limit_minutes]) }}</span>@endif
            @if($standard && ! $isOpen)
            <span><x-icon name="arrow-repeat" class="me-1" />
                @if($state['attempts_left'] === null) {{ __('lms.attempts_unlimited') }}
                @else {{ trans_choice('lms.attempts_left', $state['attempts_left'], ['count' => $state['attempts_left']]) }} @endif
            </span>
            @endif
        </div>
    </div>

    @if($openReview !== null)
        <div class="alert alert-success py-2 small"><x-icon name="check-circle" class="me-1" />{{ __('learn.exercise_done') }}</div>
        @foreach($questions as $question)
            <div class="mb-4">
                <div class="fw-semibold mb-2">{{ $loop->iteration }}. {{ $question->question }}</div>
                <div class="small text-muted mb-1">{{ __('learn.your_answer') }}</div>
                <div class="border rounded bg-white p-2 mb-2" style="white-space:pre-wrap">{{ is_array($openReview[$question->id] ?? null) ? '' : ($openReview[$question->id] ?? '') }}</div>
                <div class="model-answer"><div class="small fw-semibold text-success mb-1"><x-icon name="check2-square" class="me-1" />{{ __('learn.model_answer') }}</div>{!! nl2br(e($question->model_answer)) !!}</div>
            </div>
        @endforeach
    @elseif($standard && $state['last'] && ! $isOpen)
        <div class="alert {{ $state['passed'] ? 'alert-success' : 'alert-warning' }} py-2 small">
            @if($state['passed'])
                <x-icon name="check-circle" class="me-1" />{{ __('lms.quiz_passed_best', ['score' => (float) $state['best']]) }}
            @else
                <x-icon name="info-circle" class="me-1" />{{ __('lms.quiz_last_score', ['score' => (float) $state['last']->score, 'pass' => $passScore]) }}
            @endif
        </div>
    @endif

    @if($openReview !== null)
        {{-- already handed in: nothing more to submit --}}
    @elseif($noAttemptsLeft)
        <div class="alert alert-secondary mb-0">{{ __('lms.quiz_no_attempts_left') }}</div>
    @else
        @if($quiz->time_limit_minutes)
            <div id="quizIntro" class="text-center py-3">
                <p class="mb-3">{{ __('lms.timed_quiz_intro', ['minutes' => $quiz->time_limit_minutes]) }}</p>
                <button type="button" class="btn btn-primary" id="startQuizBtn"><x-icon name="play-fill" class="me-1" />{{ __('lms.start_quiz') }}</button>
            </div>
        @endif

        <form id="quizForm" @if($quiz->time_limit_minutes) hidden @endif novalidate>
            @if($quiz->time_limit_minutes)
                <div class="sticky-top bg-light py-2 mb-2 text-end" style="top:0">
                    <span class="quiz-timer" id="quizTimer" aria-live="polite">--:--</span>
                </div>
            @endif
            @foreach($questions as $question)
                <fieldset class="mb-4 quiz-question" data-question="{{ $question->id }}">
                    <legend class="fs-6 fw-semibold mb-2">{{ $loop->iteration }}. {{ $question->question }}
                        @if($question->type === 'multiple')<span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">{{ __('lms.several_answers') }}</span>@endif
                    </legend>
                    @if($question->isOpen())
                        <textarea class="form-control quiz-open-answer" name="q{{ $question->id }}" rows="5" maxlength="10000" data-open-answer
                                  placeholder="{{ __('learn.your_answer_placeholder') }}" aria-label="{{ __('learn.your_answer') }}"></textarea>
                    @endif
                    @foreach($question->options as $option)
                        <label class="quiz-option d-flex align-items-center gap-2 mb-2" data-option="{{ $option->id }}">
                            <input class="form-check-input mt-0" type="{{ $question->type === 'multiple' ? 'checkbox' : 'radio' }}"
                                   name="q{{ $question->id }}" value="{{ $option->id }}">
                            <span>{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                    <div class="small mt-1 review-note" hidden></div>
                </fieldset>
            @endforeach

            <div class="d-flex flex-wrap align-items-center gap-3">
                <button type="submit" class="btn btn-primary" id="submitQuizBtn"><x-icon name="send" class="me-1" />{{ __('lms.submit_answers') }}</button>
                <div id="quizResult" class="fw-semibold" aria-live="polite"></div>
            </div>
            <div id="knowledgeResult" class="mt-3" hidden></div>
        </form>
    @endif
</div>
