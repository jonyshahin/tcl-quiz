<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AnswerOption;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(private readonly QuizService $quiz) {}

    /**
     * Public start screen.
     */
    public function start(): Response
    {
        return Inertia::render('quiz/start', [
            'totalQuestions' => $this->quiz->totalQuestions(),
        ]);
    }

    /**
     * Create a new attempt and send the player to the first question.
     */
    public function begin(): RedirectResponse
    {
        if ($this->quiz->totalQuestions() === 0) {
            return redirect()->route('quiz.start');
        }

        $attempt = $this->quiz->startAttempt();

        return redirect()->route('quiz.question', [
            'attempt' => $attempt->session_token,
            'index' => 0,
        ]);
    }

    /**
     * Render a single question by its zero-based index.
     */
    public function question(QuizAttempt $attempt, int $index): Response|RedirectResponse
    {
        $questions = $this->quiz->questions();

        if ($index < 0 || $index >= $questions->count()) {
            return redirect()->route('quiz.result', ['attempt' => $attempt->session_token]);
        }

        $question = $questions[$index];
        $answers = $attempt->answers ?? [];
        $stored = $answers[(string) $question->id] ?? null;

        // Only a correctly-answered question is locked; a lingering wrong pick
        // is treated as unanswered so the player can keep trying.
        $answeredResult = null;
        if ($stored !== null && ($stored['is_correct'] ?? false) === true) {
            $answeredResult = [
                'selected_option_id' => (int) $stored['selected_option_id'],
                'is_correct' => true,
                'explanation' => $question->explanation,
            ];
        }

        return Inertia::render('quiz/question', [
            'token' => $attempt->session_token,
            'index' => $index,
            'total' => $questions->count(),
            'isLast' => $index === $questions->count() - 1,
            'question' => [
                'id' => $question->id,
                'prompt' => $question->prompt,
                'options' => $question->answerOptions
                    ->map(fn (AnswerOption $o) => [
                        'id' => $o->id,
                        'label' => $o->label,
                        'text' => $o->text,
                    ])->values(),
            ],
            'answeredResult' => $answeredResult,
        ]);
    }

    /**
     * Record an answer and return authoritative correctness as JSON.
     */
    public function answer(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['required', 'integer'],
        ]);

        $question = $this->quiz->questions()->firstWhere('id', $validated['question_id']);

        if ($question === null) {
            throw ValidationException::withMessages([
                'question_id' => 'This question is not part of the quiz.',
            ]);
        }

        $selected = $question->answerOptions->firstWhere('id', $validated['option_id']);

        if ($selected === null) {
            throw ValidationException::withMessages([
                'option_id' => 'This option does not belong to the question.',
            ]);
        }

        $result = $this->quiz->recordAnswer($attempt, $question, $selected);

        return response()->json($result);
    }

    /**
     * Finalize the attempt and show the winner/loser results screen.
     */
    public function result(QuizAttempt $attempt): Response
    {
        $attempt = $this->quiz->finalize($attempt);
        $attempt->load('lead');

        return Inertia::render('quiz/result', [
            'token' => $attempt->session_token,
            'isWinner' => $attempt->is_winner,
            'correctCount' => $attempt->correct_count,
            'totalQuestions' => $attempt->total_questions,
            'score' => $attempt->scoreLabel(),
            'leadSubmitted' => $attempt->lead !== null,
        ]);
    }
}
