<?php

declare(strict_types=1);

use App\Services\QuestionBankParser;
use Tests\TestCase;

// The parser uses the Log facade and resource_path(), so boot the Laravel app.
uses(TestCase::class);

function parser(): QuestionBankParser
{
    return new QuestionBankParser;
}

it('parses the provided question bank format', function () {
    $content = <<<'TXT'
    What is the key advantage of the CAN bus communication protocol used in TCL VRF compared to traditional systems?

    A) 10 times faster and non-polar (not affected by reversing + and - wires)
    B) Functions completely wirelessly without any cables
    C) Requires complex fiber optic cabling
    D) Needs a daily manual reboot

    A) 10 times faster and non-polar (not affected by reversing + and - wires)

    What is the ultra-wide outdoor operating temperature range for the TCL VRF system during cooling?

    A) From 0°C to 40°C only
    B) From -5°C to 56°C
    C) From 20°C to 45°C
    D) It stops working above 48°C

    B) From -5°C to 56°C
    TXT;

    $questions = parser()->parse($content);

    expect($questions)->toHaveCount(2);

    expect($questions[0]['prompt'])->toContain('CAN bus communication');
    expect($questions[0]['options'])->toHaveCount(4);

    // Correct answer matched by the A)/B)/... prefix.
    $correctFirst = collect($questions[0]['options'])->firstWhere('is_correct', true);
    expect($correctFirst['label'])->toBe('A');
    expect(collect($questions[0]['options'])->where('is_correct', true))->toHaveCount(1);

    $correctSecond = collect($questions[1]['options'])->firstWhere('is_correct', true);
    expect($correctSecond['label'])->toBe('B');
    expect($correctSecond['text'])->toBe('From -5°C to 56°C');
});

it('matches the correct answer by exact text when the prefix letter is absent', function () {
    $content = <<<'TXT'
    Which refrigerant does the system use?

    A) R32
    B) R410A
    C) R22
    D) R290

    R410A
    TXT;

    $questions = parser()->parse($content);

    $correct = collect($questions[0]['options'])->firstWhere('is_correct', true);
    expect($correct['label'])->toBe('B');
});

it('skips malformed blocks without throwing', function () {
    $content = <<<'TXT'
    A valid question?

    A) One
    B) Two
    C) Three
    D) Four

    A) One

    A broken question missing options

    A) Only one option here

    A) Only one option here
    TXT;

    $questions = parser()->parse($content);

    // Only the well-formed question survives; the malformed block is skipped.
    expect($questions)->toHaveCount(1);
    expect($questions[0]['prompt'])->toBe('A valid question?');
});

it('returns an empty array for a missing file', function () {
    expect(parser()->parseFile('/does/not/exist.txt'))->toBe([]);
});

it('parses the real bundled question file', function () {
    $questions = parser()->parseFile(resource_path('brand/questions and answers.txt'));

    expect($questions)->toHaveCount(2);
    expect(collect($questions[0]['options'])->where('is_correct', true))->toHaveCount(1);
});
