<?php

declare(strict_types=1);

use App\Models\AnswerOption;
use App\Models\Lead;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function admin(): User
{
    return User::factory()->admin()->create();
}

function validQuestionPayload(array $overrides = []): array
{
    return array_merge([
        'prompt' => 'What temperature range?',
        'explanation' => 'Because engineering.',
        'order' => 0,
        'is_active' => true,
        'correct_index' => 1,
        'options' => [
            ['label' => 'A', 'text' => 'Option A'],
            ['label' => 'B', 'text' => 'Option B'],
            ['label' => 'C', 'text' => 'Option C'],
            ['label' => 'D', 'text' => 'Option D'],
        ],
    ], $overrides);
}

it('blocks guests and non-admins from the admin area', function () {
    $this->get(route('admin.questions.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.questions.index'))
        ->assertForbidden();
});

it('lets an admin list questions', function () {
    Question::factory()->withOptions()->create();

    $this->actingAs(admin())
        ->get(route('admin.questions.index'))
        ->assertOk();
});

it('creates a question with exactly one correct option', function () {
    $this->actingAs(admin())
        ->post(route('admin.questions.store'), validQuestionPayload(['correct_index' => 2]))
        ->assertRedirect(route('admin.questions.index'));

    $question = Question::with('answerOptions')->sole();
    expect($question->answerOptions)->toHaveCount(4);

    $correct = $question->answerOptions->where('is_correct', true);
    expect($correct)->toHaveCount(1);
    expect($correct->first()->label)->toBe('C');
});

it('requires exactly four options', function () {
    $payload = validQuestionPayload();
    array_pop($payload['options']); // now only 3

    $this->actingAs(admin())
        ->post(route('admin.questions.store'), $payload)
        ->assertSessionHasErrors('options');

    expect(Question::count())->toBe(0);
});

it('requires a valid correct_index', function () {
    $this->actingAs(admin())
        ->post(route('admin.questions.store'), validQuestionPayload(['correct_index' => 9]))
        ->assertSessionHasErrors('correct_index');
});

it('updates a question and its correct option', function () {
    $question = Question::factory()->withOptions(0)->create();

    $this->actingAs(admin())
        ->put(route('admin.questions.update', $question), validQuestionPayload([
            'prompt' => 'Updated prompt',
            'correct_index' => 3,
        ]))
        ->assertRedirect(route('admin.questions.index'));

    $question->refresh()->load('answerOptions');
    expect($question->prompt)->toBe('Updated prompt');
    $correct = $question->answerOptions->firstWhere('is_correct', true);
    expect($correct->label)->toBe('D');
    expect($question->answerOptions->where('is_correct', true))->toHaveCount(1);
});

it('deletes a question and cascades its options', function () {
    $question = Question::factory()->withOptions()->create();

    $this->actingAs(admin())
        ->delete(route('admin.questions.destroy', $question))
        ->assertRedirect(route('admin.questions.index'));

    expect(Question::count())->toBe(0);
    expect(AnswerOption::count())->toBe(0);
});

it('exports leads as csv', function () {
    Lead::factory()->create(['name' => 'CSV Person', 'email' => 'csv@example.com']);

    $response = $this->actingAs(admin())->get(route('admin.leads.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    $response->streamedContent(); // ensure the stream renders without error
});
