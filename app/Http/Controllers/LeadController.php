<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function __construct(private readonly QuizService $quiz) {}

    /**
     * Capture a lead on the results screen. One lead per attempt.
     */
    public function store(StoreLeadRequest $request, QuizAttempt $attempt): RedirectResponse
    {
        $attempt = $this->quiz->finalize($attempt);

        Lead::firstOrCreate(
            ['quiz_attempt_id' => $attempt->id],
            [
                ...$request->validated(),
                'is_winner' => $attempt->is_winner,
                'score' => $attempt->scoreLabel(),
            ],
        );

        return redirect()
            ->route('quiz.result', ['attempt' => $attempt->session_token])
            ->with('success', 'Thanks! Your details were saved.');
    }
}
