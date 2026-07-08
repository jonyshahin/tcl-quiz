<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * Create N questions, each with four options, the correct one at $correctIndex.
 *
 * @return Collection<int, Question>
 */
function makeQuestions(int $count = 2, int $correctIndex = 0)
{
    return collect(range(1, $count))->map(
        fn (int $i) => Question::factory()->withOptions($correctIndex)->create(['order' => $i])
    );
}

it('starts a quiz, creating an attempt and redirecting to the first question', function () {
    makeQuestions(2);

    $response = $this->post(route('quiz.begin'));

    $attempt = QuizAttempt::sole();
    expect($attempt->total_questions)->toBe(2);
    $response->assertRedirect(route('quiz.question', [
        'attempt' => $attempt->session_token,
        'index' => 0,
    ]));
});

it('marks the attempt a winner when every answer is correct', function () {
    $questions = makeQuestions(2, correctIndex: 0);
    $attempt = app(QuizService::class)->startAttempt();

    foreach ($questions as $q) {
        $correct = $q->answerOptions->firstWhere('is_correct', true);
        $this->postJson(route('quiz.answer', $attempt->session_token), [
            'question_id' => $q->id,
            'option_id' => $correct->id,
        ])->assertOk()->assertJson(['is_correct' => true]);
    }

    $this->get(route('quiz.result', $attempt->session_token))->assertOk();

    $attempt->refresh();
    expect($attempt->correct_count)->toBe(2);
    expect($attempt->is_winner)->toBeTrue();
});

it('is not a winner when at least one answer is wrong', function () {
    $questions = makeQuestions(2, correctIndex: 0);
    $attempt = app(QuizService::class)->startAttempt();

    // Answer the first correctly, the second wrongly.
    $first = $questions[0]->answerOptions->firstWhere('is_correct', true);
    $wrong = $questions[1]->answerOptions->firstWhere('is_correct', false);

    $this->postJson(route('quiz.answer', $attempt->session_token), [
        'question_id' => $questions[0]->id, 'option_id' => $first->id,
    ])->assertJson(['is_correct' => true]);

    $this->postJson(route('quiz.answer', $attempt->session_token), [
        'question_id' => $questions[1]->id, 'option_id' => $wrong->id,
    ])->assertJson(['is_correct' => false]);

    $this->get(route('quiz.result', $attempt->session_token));

    $attempt->refresh();
    expect($attempt->correct_count)->toBe(1);
    expect($attempt->is_winner)->toBeFalse();
});

it('computes the score server-side and cannot be spoofed by the client payload', function () {
    $questions = makeQuestions(2, correctIndex: 0);
    $attempt = app(QuizService::class)->startAttempt();

    // Client tries to inject correctness/score fields — they must be ignored.
    foreach ($questions as $q) {
        $wrong = $q->answerOptions->firstWhere('is_correct', false);
        $this->postJson(route('quiz.answer', $attempt->session_token), [
            'question_id' => $q->id,
            'option_id' => $wrong->id,
            'is_correct' => true,
            'correct_count' => 99,
            'is_winner' => true,
        ])->assertJson(['is_correct' => false]);
    }

    $this->get(route('quiz.result', $attempt->session_token));

    $attempt->refresh();
    expect($attempt->correct_count)->toBe(0);
    expect($attempt->is_winner)->toBeFalse();
});

it('locks an answer so it cannot be changed once recorded', function () {
    $question = makeQuestions(1, correctIndex: 0)->first();
    $attempt = app(QuizService::class)->startAttempt();

    $correct = $question->answerOptions->firstWhere('is_correct', true);
    $wrong = $question->answerOptions->firstWhere('is_correct', false);

    $this->postJson(route('quiz.answer', $attempt->session_token), [
        'question_id' => $question->id, 'option_id' => $correct->id,
    ])->assertJson(['is_correct' => true]);

    // A second, different answer for the same question returns the original result.
    $this->postJson(route('quiz.answer', $attempt->session_token), [
        'question_id' => $question->id, 'option_id' => $wrong->id,
    ])->assertJson(['is_correct' => true, 'already_answered' => true]);

    $attempt->refresh();
    expect($attempt->correct_count)->toBe(1);
});

it('rejects an option that does not belong to the question', function () {
    $questions = makeQuestions(2, correctIndex: 0);
    $attempt = app(QuizService::class)->startAttempt();

    $foreignOption = $questions[1]->answerOptions->first();

    $this->postJson(route('quiz.answer', $attempt->session_token), [
        'question_id' => $questions[0]->id,
        'option_id' => $foreignOption->id,
    ])->assertStatus(422);
});
