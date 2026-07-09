<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Draw;
use App\Models\DrawWinner;
use App\Models\Lead;
use Illuminate\Support\Collection;

class DrawService
{
    /** Maximum winners revealed in a single draw. */
    public const MAX_WINNERS = 3;

    /** Cap on how many names are streamed to the client roulette. */
    private const REEL_CAP = 120;

    /**
     * The eligible pool: one representative lead per distinct person.
     *
     * Two leads that share the same non-empty phone are treated as one person
     * (the earliest wins). When a lead has no phone, we fall back to de-duping
     * by its lowercased, trimmed name.
     *
     * @return Collection<int, Lead>
     */
    public function eligibleLeads(): Collection
    {
        $seen = [];

        return Lead::query()
            ->orderBy('id')
            ->get()
            ->filter(function (Lead $lead) use (&$seen): bool {
                $phone = preg_replace('/\s+/', '', (string) ($lead->phone ?? ''));

                $key = $phone !== ''
                    ? 'phone:'.$phone
                    : 'name:'.mb_strtolower(trim((string) $lead->name));

                if (isset($seen[$key])) {
                    return false;
                }

                $seen[$key] = true;

                return true;
            })
            ->values();
    }

    public function eligibleCount(): int
    {
        return $this->eligibleLeads()->count();
    }

    /**
     * The maximum number of winners this pool can produce (never more than 3).
     */
    public function maxWinners(): int
    {
        return min(self::MAX_WINNERS, $this->eligibleCount());
    }

    /**
     * A shuffled list of display names only, for the client-side roulette reel.
     *
     * Never includes phone numbers — masked phones are only ever exposed for
     * the actual drawn winners via pickNext().
     *
     * @return list<string>
     */
    public function reelNames(): array
    {
        return array_values(
            $this->eligibleLeads()
                ->pluck('name')
                ->map(fn ($name) => (string) $name)
                ->shuffle()
                ->take(self::REEL_CAP)
                ->all()
        );
    }

    /**
     * Pick one random eligible lead not already chosen in this draw, persist it
     * as the next positioned winner, and return the created DrawWinner.
     *
     * Server-authoritative: the client never chooses the winner. Returns null
     * when the pool is exhausted or the draw is already full.
     */
    public function pickNext(Draw $draw): ?DrawWinner
    {
        $takenLeadIds = $draw->winners()->pluck('lead_id')->all();
        $nextPosition = count($takenLeadIds) + 1;

        if ($nextPosition > self::MAX_WINNERS) {
            return null;
        }

        $remaining = $this->eligibleLeads()
            ->reject(fn (Lead $lead) => in_array($lead->id, $takenLeadIds, true))
            ->values();

        if ($remaining->isEmpty()) {
            return null;
        }

        $candidate = $remaining->random();

        return $draw->winners()->create([
            'lead_id' => $candidate->id,
            'position' => $nextPosition,
        ]);
    }
}
