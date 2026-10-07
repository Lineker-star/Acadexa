@extends('layouts.instructor')
@section('title', $quiz->title())
@section('page-title', __('learn.assessment_editor'))

@php
    $count = $quiz->questions->count();
    $min = $quiz->minQuestions();
    $pass = config('lms.assessment.pass_percent');
    $isFinal = $quiz->isFinal();
    $isOpen = $quiz->usesOpenQuestions();
    $modalId = $isOpen ? '#openQuestionModal' : '#questionModal';
    $scopeHelp = [
        'lesson' => __('learn.scope_help_lesson', ['min' => $min, 'pass' => $pass]),
        'module' => __('learn.scope_help_module', ['min' => $min, 'pass' => $pass]),
        'course' => __('learn.scope_help_course', ['min' => $min, 'pass' => $pass]),
    ][$quiz->scope];
@endphp

@section('content')
<nav aria-label="{{ __('Breadcrumb') }}" class="mb-2">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('instructor.courses.edit', ['course' => $course, 'tab' => 'assessments']) }}">{{ \Illuminate\Support\Str::limit($course->title(), 40) }}</a></li>
        @if($quiz->scope === 'lesson')
            <li class="breadcrumb-item"><a href="{{ route('instructor.lessons.edit', $quiz->lesson) }}">{{ \Illuminate\Support\Str::limit($quiz->lesson->title(), 40) }}</a></li>
        @elseif($quiz->scope === 'module')
            <li class="breadcrumb-item">{{ \Illuminate\Support\Str::limit($quiz->module->title(), 40) }}</li>
        @endif
        <li class="breadcrumb-item active">{{ $quiz->title() }}</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="fw-bold mb-0"><x-icon :name="$quiz->icon()" class="text-primary me-2" />{{ $quiz->title() }}</h4>
    <span class="badge fs-6 {{ $count >= $min ? 'bg-success' : 'bg-warning text-dark' }}">
        {{ __('learn.questions_of_min', ['count' => $count, 'min' => $min]) }}
    </span>
</div>

@if($locked)
    <div class="alert alert-warning"><x-icon name="lock" class="me-1" />{{ __('lms.course_locked_pending') }}</div>
@endif

<div class="row g-4">
<div class="col-xl-8">
    <div class="alert {{ $count >= $min ? 'alert-success' : 'alert-info' }} small">
        <x-icon :name="$count >= $min ? 'check-circle' : 'info-circle'" class="me-1" />{{ $scopeHelp }}
        @if($count < $min) <strong>{{ trans_choice('learn.questions_missing', $min - $count, ['count' => $min - $count]) }}</strong>@endif
        <div class="progress mt-2" style="height:6px"><div class="progress-bar {{ $count >= $min ? 'bg-success' : '' }}" style="width:{{ min(100, $count / max(1, $min) * 100) }}%"></div></div>
    </div>

    {{-- Settings (multiple-choice assessments only: an open exercise is not graded automatically) --}}
    @unless($isOpen)
    <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
        <h5 class="fw-bold mb-3">{{ __('lms.save_settings') }}</h5>
        <form method="POST" action="{{ route('instructor.quizzes.update', $quiz) }}">
            @csrf @method('PUT')
            <fieldset @disabled($locked) class="row g-3 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold">{{ __('lms.passing_score') }}</label>
                <div class="input-group input-group-sm"><input type="number" name="passing_score" class="form-control @error('passing_score') is-invalid @enderror" min="{{ $pass }}" max="100" value="{{ old('passing_score', $quiz->effectivePassingScore()) }}" required><span class="input-group-text">%</span></div>
                <div class="form-text">{{ __('learn.pass_minimum', ['pass' => $pass]) }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label small fw-semibold">{{ __('lms.max_attempts') }}</label>
                <input type="number" name="max_attempts" class="form-control form-control-sm" min="1" max="100" value="{{ $quiz->max_attempts }}" placeholder="{{ __('lms.unlimited') }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label small fw-semibold">{{ __('lms.time_limit') }}</label>
                <div class="input-group input-group-sm"><input type="number" name="time_limit_minutes" class="form-control" min="1" max="600" value="{{ $quiz->time_limit_minutes }}" placeholder="{{ __('lms.none') }}"><span class="input-group-text">{{ __('min') }}</span></div>
            </div>
            <div class="col-sm-8">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1" id="shuffleQ" @checked($quiz->shuffle_questions)>
                    <label class="form-check-label small" for="shuffleQ">{{ __('lms.shuffle_questions') }}</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="show_correct_answers" value="1" id="showCorrect" @checked($quiz->show_correct_answers)>
                    <label class="form-check-label small" for="showCorrect">{{ __('lms.show_correct_answers') }}</label>
                </div>
            </div>
            <div class="col-sm-4 text-end"><button class="btn btn-sm btn-outline-primary">{{ __('lms.save_settings') }}</button></div>
            </fieldset>
        </form>
        @if($quiz->time_limit_minutes)
            <p class="small text-muted mt-2 mb-0"><x-icon name="info-circle" class="me-1" />{{ __('lms.timed_quiz_offline_note') }}</p>
        @endif
        @if($isFinal)
            <p class="small text-muted mt-2 mb-0"><x-icon name="graph-up-arrow" class="me-1" />{{ __('learn.final_settings_help') }}</p>
        @endif
    </div>

    @endunless

    {{-- Questions --}}
    <div class="bg-white rounded-xl shadow-brand p-4 mb-4" id="quiz">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="fw-bold mb-0">{{ trans_choice('lms.questions_count', $count, ['count' => $count]) }}</h5>
            @unless($locked)
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#importBox"><x-icon name="upload" class="me-1" />{{ __('learn.import_questions') }}</button>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="{{ $modalId }}"><x-icon name="plus-lg" class="me-1" />{{ __('lms.add_question') }}</button>
            </div>
            @endunless
        </div>

        @unless($locked)
        <div class="collapse {{ $errors->has('questions_text') ? 'show' : '' }} mb-3" id="importBox">
            <form method="POST" action="{{ route('instructor.questions.import', $quiz) }}" class="border rounded p-3 bg-light">
                @csrf
                <label class="form-label small fw-semibold" for="questionsText">{{ $isOpen ? __('learn.import_open_help') : __('learn.import_help') }}</label>
                <div class="row g-3">
                    <div class="col-md-7">
                        <textarea name="questions_text" id="questionsText" rows="10" class="form-control form-control-sm font-monospace @error('questions_text') is-invalid @enderror">{{ old('questions_text') }}</textarea>
                        @error('questions_text')<div class="invalid-feedback">{!! implode('<br>', array_map('e', $errors->get('questions_text'))) !!}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <div class="small text-muted mb-1">{{ __('learn.import_example') }}</div>
<pre class="small bg-white border rounded p-2 mb-0" style="white-space:pre-wrap">{{ $isOpen ? __('learn.import_open_sample') : __('learn.import_sample') }}</pre>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary mt-2"><x-icon name="upload" class="me-1" />{{ __('learn.import_questions') }}</button>
            </form>
        </div>
        @endunless

        @if($errors->has('model_answer') || $errors->has('question'))
            <div class="alert alert-danger small py-2">{{ $errors->first('model_answer') ?: $errors->first('question') }}</div>
        @endif
        <div id="questionList" data-reorder-url="{{ $locked ? '' : route('instructor.questions.reorder', $quiz) }}">
            @forelse($quiz->questions as $question)
                <div class="question-card" data-question-id="{{ $question->id }}">
                    <div class="q-head">
                        <x-icon name="grip-vertical" class="drag-handle" />
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $loop->iteration }}. {{ $question->question }}</div>
                            <div class="small text-muted">
                                {{ $question->isOpen() ? __('learn.open_question') : ($question->type === 'multiple' ? __('lms.multiple_answers') : __('lms.single_answer')) }}
                                @if($isFinal && $question->module) · <x-icon name="collection" class="me-1" />{{ $question->module->title() }} @endif
                            </div>
                        </div>
                        @unless($locked)
                        @php
                            $questionData = [
                                'question'    => $question->question,
                                'type'        => $question->type,
                                'module_id'   => $question->module_id,
                                'explanation' => $question->explanation,
                                'model_answer' => $question->model_answer,
                                'options'     => $question->options->map(fn ($o) => ['text' => $o->option_text, 'correct' => $o->is_correct])->all(),
                            ];
                        @endphp
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="{{ $modalId }}"
                                data-action="{{ route('instructor.questions.update', $question) }}"
                                data-question="{{ json_encode($questionData) }}" aria-label="{{ __('lms.edit') }}">
                            <x-icon name="pencil" /></button>
                        <form method="POST" action="{{ route('instructor.questions.destroy', $question) }}" data-confirm="{{ __('lms.confirm_delete_question') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger" aria-label="{{ __('lms.delete') }}"><x-icon name="trash" /></button>
                        </form>
                        @endunless
                    </div>
                    @if($question->isOpen())
                        <div class="model-answer ms-4"><div class="small fw-semibold text-success mb-1"><x-icon name="check2-square" class="me-1" />{{ __('learn.model_answer') }}</div>{!! nl2br(e($question->model_answer)) !!}</div>
                    @endif
                    <ul class="q-options list-unstyled mb-0">
                        @foreach($question->options as $option)
                            <li class="{{ $option->is_correct ? 'is-correct' : '' }}"><x-icon :name="$option->is_correct ? 'bi-check-circle-fill' : 'bi-circle'" class="me-1" />{{ $option->option_text }}</li>
                        @endforeach
                    </ul>
                    @if($question->explanation)
                        <div class="small text-muted mt-1 ps-4"><x-icon name="lightbulb" class="me-1" />{{ $question->explanation }}</div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-4 border rounded"><x-icon name="question-circle" class="fs-3 d-block mb-2" />{{ __('lms.no_questions') }}</div>
            @endforelse
        </div>
    </div>
</div>

{{-- ─── Assessment plan of the course ─── --}}
<div class="col-xl-4">
    @include('instructor.quizzes.partials.plan', ['course' => $course, 'current' => $quiz])
</div>
</div>

@if($isOpen)
{{-- Open question + detailed answer (module exercise) --}}
<div class="modal fade" id="openQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" class="modal-content" id="openQuestionForm" data-create-action="{{ route('instructor.questions.store', $quiz) }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('learn.open_question') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('lms.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('lms.question') }} *</label>
                    <textarea name="question" class="form-control" rows="3" maxlength="2000" required></textarea>
                </div>
                <label class="form-label fw-semibold">{{ __('learn.model_answer') }} *</label>
                <textarea name="model_answer" class="form-control" rows="9" minlength="10" maxlength="20000" required placeholder="{{ __('learn.model_answer_placeholder') }}"></textarea>
                <div class="form-text">{{ __('learn.model_answer_help') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lms.cancel') }}</button>
                <button class="btn btn-primary">{{ __('lms.save') }}</button>
            </div>
        </form>
    </div>
</div>
@else
<div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" class="modal-content" id="questionForm" data-create-action="{{ route('instructor.questions.store', $quiz) }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lms.question') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('lms.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('lms.question') }} *</label>
                    <textarea name="question" class="form-control" rows="2" maxlength="2000" required></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">{{ __('lms.answer_type') }}</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="single">{{ __('lms.single_answer') }}</option>
                            <option value="multiple">{{ __('lms.multiple_answers') }}</option>
                        </select>
                    </div>
                    @if($isFinal)
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">{{ __('learn.question_module') }}</label>
                        <select name="module_id" class="form-select form-select-sm">
                            <option value="">{{ __('learn.no_module') }}</option>
                            @foreach($course->modules as $module)
                                <option value="{{ $module->id }}">{{ __('lms.module') }} {{ $loop->iteration }} : {{ $module->title() }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('learn.question_module_help') }}</div>
                    </div>
                    @endif
                </div>
                <label class="form-label fw-semibold">{{ __('lms.options') }} <span class="text-muted small fw-normal">— {{ __('lms.tick_correct') }}</span></label>
                <div data-role="options"></div>
                <button type="button" class="btn btn-sm btn-link px-0" data-action="add-option"><x-icon name="plus-lg" class="me-1" />{{ __('lms.add_option') }}</button>
                <div class="mt-3">
                    <label class="form-label fw-semibold">{{ __('learn.explanation') }}</label>
                    <textarea name="explanation" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="{{ __('learn.explanation_placeholder') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lms.cancel') }}</button>
                <button class="btn btn-primary">{{ __('lms.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@vite(['resources/js/course-builder.js'])
@endpush
