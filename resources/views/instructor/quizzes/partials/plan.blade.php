{{-- Assessment plan: every lesson quiz, module exercise and the final evaluation, with their status. --}}
@php
    $current ??= null;
    $badge = function ($quiz, int $min) {
        $n = $quiz ? $quiz->questionCount() : 0;
        return [$n, $n >= $min ? 'bg-success' : ($n > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border')];
    };
    $mins = config('lms.assessment.min_questions');
@endphp
<div class="bg-white rounded-xl shadow-brand p-4">
    <h6 class="fw-bold mb-1"><x-icon name="diagram-3" class="me-1" />{{ __('learn.assessment_plan') }}</h6>
    <p class="small text-muted">{{ __('learn.assessment_plan_help') }}</p>
    @foreach($course->modules as $module)
        <div class="small fw-semibold mt-3 mb-1">{{ __('lms.module') }} {{ $loop->iteration }} : {{ $module->title() }}</div>
        <ul class="list-unstyled small mb-0">
            @foreach($module->lessons->whereIn('type', ['video', 'text', 'quiz']) as $lesson)
                @php [$n, $cls] = $badge($lesson->quiz, $mins['lesson']); @endphp
                <li class="d-flex align-items-center gap-2 py-1 {{ $current && $current->lesson_id === $lesson->id ? 'fw-bold' : '' }}">
                    <x-icon name="patch-question" class="text-muted" />
                    <a href="{{ route('instructor.assessments.lesson', $lesson) }}" class="flex-grow-1 text-truncate">{{ $lesson->title() }}</a>
                    <span class="badge {{ $cls }}">{{ $n }}/{{ $mins['lesson'] }}</span>
                </li>
            @endforeach
            @php [$n, $cls] = $badge($module->exam, $mins['module']); @endphp
            <li class="d-flex align-items-center gap-2 py-1 {{ $current && $current->module_id === $module->id ? 'fw-bold' : '' }}">
                <x-icon name="clipboard-check" class="text-primary" />
                <a href="{{ route('instructor.assessments.module', $module) }}" class="flex-grow-1">{{ __('learn.module_exercise') }}</a>
                <span class="badge {{ $cls }}">{{ $n }}/{{ $mins['module'] }}</span>
            </li>
        </ul>
    @endforeach
    @php [$n, $cls] = $badge($course->finalExam, $mins['course']); @endphp
    <hr>
    <div class="d-flex align-items-center gap-2 small {{ $current && $current->isFinal() ? 'fw-bold' : '' }}">
        <x-icon name="trophy" class="text-warning" />
        <a href="{{ route('instructor.assessments.final', $course) }}" class="flex-grow-1 fw-semibold">{{ __('learn.final_evaluation') }}</a>
        <span class="badge {{ $cls }}">{{ $n }}/{{ $mins['course'] }}</span>
    </div>
</div>
