<div id="quizContainer" class="mt-3 p-4 border rounded-xl bg-light">
    <h5 class="mb-4">📝 Quiz: {{ $quiz->questions->count() }} Questions</h5>
    @php $attempt = $quiz->userAttempt(auth()->id()); @endphp

    @if($attempt)
    <div class="alert {{ $attempt->passed ? 'alert-success' : 'alert-warning' }}">
        <strong>Your last score: {{ $attempt->score }}%</strong>
        {{ $attempt->passed ? ' — Passed! 🎉' : ' — Keep trying! (Passing: '.$quiz->passing_score.'%)' }}
    </div>
    @endif

    <div id="quizForm">
        @foreach($quiz->questions as $q)
        <div class="mb-4 quiz-question" data-question="{{ $q->id }}">
            <p class="fw-bold" style="font-size:.95rem;">{{ $loop->iteration }}. {{ $q->question }}</p>
            <div class="quiz-options">
                @foreach($q->options as $opt)
                <div class="quiz-option" data-option="{{ $opt->id }}" onclick="selectOption(this, {{ $q->id }}, {{ $opt->id }}, '{{ $q->type }}')">
                    {{ $opt->option_text }}
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between align-items-center mt-4">
            <button id="submitQuizBtn" class="btn btn-primary" onclick="submitQuiz({{ $quiz->id }})">
                Submit Quiz
            </button>
            <div id="quizResult" class="fw-bold" style="display:none;"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const quizAnswers = {};

function selectOption(el, questionId, optionId, type) {
    const container = el.closest('.quiz-options');
    if (type === 'single') {
        container.querySelectorAll('.quiz-option').forEach(o => o.classList.remove('selected'));
        quizAnswers[questionId] = [optionId];
    } else {
        if (!quizAnswers[questionId]) quizAnswers[questionId] = [];
        if (el.classList.contains('selected')) {
            el.classList.remove('selected');
            quizAnswers[questionId] = quizAnswers[questionId].filter(id => id !== optionId);
        } else {
            quizAnswers[questionId].push(optionId);
        }
    }
    el.classList.toggle('selected', type === 'single' || quizAnswers[questionId].includes(optionId));
}

async function submitQuiz(quizId) {
    const btn = document.getElementById('submitQuizBtn');
    btn.disabled = true;
    btn.textContent = 'Submitting...';
    try {
        const res = await fetch(`/quiz/${quizId}/attempt`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ answers: quizAnswers }),
        });
        const data = await res.json();
        const result = document.getElementById('quizResult');
        result.style.display = 'block';
        result.style.color = data.passed ? '#065F46' : '#991B1B';
        result.innerHTML = `Score: ${data.score}% — ${data.message} (${data.correct}/${data.total} correct)`;
        showToast(data.message, data.passed ? 'success' : 'warning');
    } catch(e) { console.error(e); btn.disabled = false; btn.textContent = 'Submit Quiz'; }
}
</script>
@endpush
