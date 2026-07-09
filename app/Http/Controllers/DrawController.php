<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Services\DrawService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DrawController extends Controller
{
    public function __construct(private readonly DrawService $draws) {}

    /**
     * Public big-screen Lucky Draw page. Does not create a draw row (the client
     * calls start() on the first press) to avoid empty-draw spam.
     */
    public function index(): Response
    {
        return Inertia::render('draw/index', [
            'reelNames' => $this->draws->reelNames(),
            'eligibleCount' => $this->draws->eligibleCount(),
            'maxWinners' => $this->draws->maxWinners(),
        ]);
    }

    /**
     * Begin a fresh, persisted draw and return its id. Called on the first
     * press and again on "Draw again".
     */
    public function start(): JsonResponse
    {
        $eligibleCount = $this->draws->eligibleCount();

        if ($eligibleCount < 1) {
            return response()->json([
                'message' => 'No participants yet — check back after people play.',
            ], 422);
        }

        $draw = Draw::create([
            'eligible_count' => $eligibleCount,
        ]);

        return response()->json([
            'drawId' => $draw->id,
            'maxWinners' => $this->draws->maxWinners(),
        ]);
    }

    /**
     * Reveal the next winner for a draw. Server-authoritative selection.
     */
    public function pick(Draw $draw): JsonResponse
    {
        $maxWinners = $this->draws->maxWinners();

        if ($maxWinners < 1) {
            return response()->json([
                'message' => 'No participants yet — check back after people play.',
            ], 422);
        }

        if ($draw->winners()->count() >= $maxWinners) {
            return response()->json([
                'message' => 'This draw is already complete.',
            ], 422);
        }

        $winner = $this->draws->pickNext($draw);

        if ($winner === null) {
            return response()->json([
                'message' => 'No more eligible participants to draw.',
            ], 422);
        }

        $winner->load('lead');
        $revealedCount = $draw->winners()->count();

        return response()->json([
            'position' => $winner->position,
            'winner' => [
                'name' => $winner->lead->name,
                'maskedPhone' => $winner->lead->maskedPhone(),
            ],
            'revealedCount' => $revealedCount,
            'maxWinners' => $maxWinners,
            'complete' => $revealedCount >= $maxWinners,
        ]);
    }
}
