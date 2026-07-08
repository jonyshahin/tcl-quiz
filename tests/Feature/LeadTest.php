<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires a name', function () {
    $attempt = QuizAttempt::factory()->completed(2, 2)->create();

    $this->post(route('quiz.lead', $attempt->session_token), [
        'name' => '',
    ])->assertSessionHasErrors('name');

    expect(Lead::count())->toBe(0);
});

it('rejects a malformed email when one is provided', function () {
    $attempt = QuizAttempt::factory()->completed(2, 2)->create();

    $this->post(route('quiz.lead', $attempt->session_token), [
        'name' => 'Ada Lovelace',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors('email');

    expect(Lead::count())->toBe(0);
});

it('stores a lead with only name and phone (email is optional)', function () {
    $attempt = QuizAttempt::factory()->completed(2, 2)->create();

    $this->post(route('quiz.lead', $attempt->session_token), [
        'name' => 'Ada Lovelace',
        'phone' => '+1 555 0100',
    ])->assertRedirect(route('quiz.result', $attempt->session_token));

    $lead = Lead::sole();
    expect($lead->name)->toBe('Ada Lovelace');
    expect($lead->phone)->toBe('+1 555 0100');
    expect($lead->email)->toBeNull();
    expect($lead->is_winner)->toBeTrue();
    expect($lead->score)->toBe('2/2');
    expect($lead->quiz_attempt_id)->toBe($attempt->id);
});

it('captures the loser snapshot correctly', function () {
    $attempt = QuizAttempt::factory()->completed(2, 1)->create();

    $this->post(route('quiz.lead', $attempt->session_token), [
        'name' => 'Grace Hopper',
    ]);

    $lead = Lead::sole();
    expect($lead->is_winner)->toBeFalse();
    expect($lead->score)->toBe('1/2');
});

it('allows only one lead per attempt', function () {
    $attempt = QuizAttempt::factory()->completed(2, 2)->create();

    $this->post(route('quiz.lead', $attempt->session_token), [
        'name' => 'First',
    ]);
    $this->post(route('quiz.lead', $attempt->session_token), [
        'name' => 'Second',
    ]);

    expect(Lead::count())->toBe(1);
    expect(Lead::sole()->name)->toBe('First');
});
