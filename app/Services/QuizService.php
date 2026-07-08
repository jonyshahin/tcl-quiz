<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class QuizService
{
    /**
     * The active questions in display order, eager-loaded with their options.
     *
     * @return Collection<int, Question>
     */
    public function questions(): Collection
    {
        return Question::query()
            ->active()
            ->ordered()
            ->with('answerOptions')
            ->get();
    }

    public function totalQuestions(): int
    {
        return Question::query()->active()->count();
    }

    /**
     * Create a fresh attempt for an anonymous run.
     */
    public function startAttempt(): QuizAttempt
    {
        return QuizAttempt::create([
            'session_token' => (string) Str::uuid(),
            'total_questions' => $this->totalQuestions(),
            'correct_count' => 0,
            'is_winner' => false,
            'answers' => [],
        ]);
    }

    /**
     * Record an answer for a question against an attempt. Scoring is authoritative
     * and server-side: the client only sends which option it picked.
     *
     * A question locks only once it is answered correctly — the player retries
     * wrong picks until they choose the right option. Wrong picks overwrite any
     * previous wrong attempt and never lock the question.
     *
     * @return array{question_id: int, selected_option_id: int, is_correct: bool, explanation: ?string, locked: bool}
     */
    public function recordAnswer(QuizAttempt $attempt, Question $question, AnswerOption $selected): array
    {
        $answers = $attempt->answers ?? [];
        $key = (string) $question->id;

        // Already answered correctly — the question is locked; return that result.
        if (isset($answers[$key]) && ($answers[$key]['is_correct'] ?? false) === true) {
            return [
                'question_id' => $question->id,
                'selected_option_id' => (int) $answers[$key]['selected_option_id'],
                'is_correct' => true,
                'explanation' => $question->explanation,
                'locked' => true,
            ];
        }

        $isCorrect = $selected->is_correct;

        $answers[$key] = [
            'selected_option_id' => $selected->id,
            'is_correct' => $isCorrect,
        ];

        $attempt->answers = $answers;
        $attempt->correct_count = $this->countCorrect($answers);
        $attempt->save();

        return [
            'question_id' => $question->id,
            'selected_option_id' => $selected->id,
            'is_correct' => $isCorrect,
            'explanation' => $isCorrect ? $question->explanation : null,
            'locked' => $isCorrect,
        ];
    }

    /**
     * Finalize an attempt: recompute the score server-side and mark it complete.
     */
    public function finalize(QuizAttempt $attempt): QuizAttempt
    {
        $total = $attempt->total_questions > 0 ? $attempt->total_questions : $this->totalQuestions();
        $correct = $this->countCorrect($attempt->answers ?? []);

        $attempt->forceFill([
            'total_questions' => $total,
            'correct_count' => $correct,
            'is_winner' => $total > 0 && $correct === $total,
            'completed_at' => $attempt->completed_at ?? now(),
        ])->save();

        return $attempt;
    }

    /**
     * @param  array<string, array{selected_option_id: int, is_correct: bool}>  $answers
     */
    private function countCorrect(array $answers): int
    {
        return count(array_filter($answers, static fn ($a) => (bool) ($a['is_correct'] ?? false)));
    }
}
