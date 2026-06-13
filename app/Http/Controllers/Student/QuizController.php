<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function attempt(Request $request, Quiz $quiz)
    {
        $request->validate(['answers' => ['required', 'array']]);

        $questions = $quiz->questions()->with('options')->get();
        $answers   = $request->answers; // ['question_id' => [option_ids]]

        $total   = $questions->count();
        $correct = 0;

        foreach ($questions as $question) {
            $correctIds   = $question->options->where('is_correct', true)->pluck('id')->toArray();
            $submitted    = (array) ($answers[$question->id] ?? []);
            sort($correctIds);
            sort($submitted);
            if ($correctIds == $submitted) {
                $correct++;
            }
        }

        $score  = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $passed = $score >= $quiz->passing_score;

        QuizAttempt::create([
            'user_id'      => $request->user()->id,
            'quiz_id'      => $quiz->id,
            'score'        => $score,
            'passed'       => $passed,
            'answers'      => $answers,
            'attempted_at' => now(),
        ]);

        return response()->json([
            'score'    => $score,
            'passed'   => $passed,
            'correct'  => $correct,
            'total'    => $total,
            'message'  => $passed ? __('messages.quiz_passed') : __('messages.quiz_failed'),
        ]);
    }
}
