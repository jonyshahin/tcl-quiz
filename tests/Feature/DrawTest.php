<?php

declare(strict_types=1);

use App\Models\Draw;
use App\Models\DrawWinner;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Create N leads with distinct phones so none are de-duped away.
 */
function seedLeads(int $count): void
{
    foreach (range(1, $count) as $i) {
        Lead::factory()->create([
            'quiz_attempt_id' => null,
            'name' => "Player {$i}",
            'phone' => '070000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
        ]);
    }
}

it('renders the draw page with names-only reel and eligible count', function () {
    seedLeads(5);

    $this->get(route('draw.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('draw/index')
            ->where('eligibleCount', 5)
            ->where('maxWinners', 3)
            ->has('reelNames', 5)
        );

    // The payload must not leak any phone digits for the whole pool.
    $response = $this->get(route('draw.index'));
    $props = $response->viewData('page')['props'];
    $reel = json_encode($props['reelNames']);
    expect($reel)->not->toContain('0700000');
});

it('starts a draw and returns a drawId', function () {
    seedLeads(5);

    $response = $this->postJson(route('draw.start'));

    $response->assertOk()->assertJsonStructure(['drawId', 'maxWinners']);
    expect(Draw::count())->toBe(1);
    expect(Draw::sole()->eligible_count)->toBe(5);
});

it('reveals three distinct winners with sequential positions and completes on the last', function () {
    seedLeads(5);

    $draw = Draw::create(['eligible_count' => 5]);

    $first = $this->postJson(route('draw.pick', $draw))->assertOk()->json();
    expect($first['position'])->toBe(1);
    expect($first['complete'])->toBeFalse();

    $second = $this->postJson(route('draw.pick', $draw))->assertOk()->json();
    expect($second['position'])->toBe(2);
    expect($second['complete'])->toBeFalse();

    $third = $this->postJson(route('draw.pick', $draw))->assertOk()->json();
    expect($third['position'])->toBe(3);
    expect($third['complete'])->toBeTrue();

    $leadIds = DrawWinner::query()->where('draw_id', $draw->id)->pluck('lead_id');
    expect($leadIds->unique()->count())->toBe(3);
    expect(DrawWinner::query()->where('draw_id', $draw->id)->pluck('position')->sort()->values()->all())
        ->toBe([1, 2, 3]);
});

it('never returns an already-chosen lead within the same draw', function () {
    // Exactly 3 eligible so any repeat would be forced if selection were buggy.
    seedLeads(3);
    $draw = Draw::create(['eligible_count' => 3]);

    $names = collect();
    for ($i = 0; $i < 3; $i++) {
        $names->push($this->postJson(route('draw.pick', $draw))->assertOk()->json('winner.name'));
    }

    expect($names->unique()->count())->toBe(3);
});

it('rejects a pick once the draw is complete', function () {
    seedLeads(5);
    $draw = Draw::create(['eligible_count' => 5]);

    for ($i = 0; $i < 3; $i++) {
        $this->postJson(route('draw.pick', $draw))->assertOk();
    }

    $this->postJson(route('draw.pick', $draw))->assertStatus(422);
    expect(DrawWinner::query()->where('draw_id', $draw->id)->count())->toBe(3);
});

it('returns a masked phone (never the full number) for winners', function () {
    Lead::factory()->create([
        'quiz_attempt_id' => null,
        'name' => 'Ada Lovelace',
        'phone' => '07701234589',
    ]);
    $draw = Draw::create(['eligible_count' => 1]);

    $winner = $this->postJson(route('draw.pick', $draw))->assertOk()->json('winner');

    expect($winner['maskedPhone'])->toBe('0770•••••89');
    expect($winner['maskedPhone'])->not->toContain('1234');
});

it('returns a friendly 422 with no rows when there are zero eligible leads', function () {
    $this->postJson(route('draw.start'))->assertStatus(422);
    expect(Draw::count())->toBe(0);

    // A pick against a (manually created) draw with an empty pool also 422s.
    $draw = Draw::create(['eligible_count' => 0]);
    $this->postJson(route('draw.pick', $draw))->assertStatus(422);
    expect(DrawWinner::count())->toBe(0);
});

it('allows drawing only up to the eligible count when fewer than three', function () {
    seedLeads(2);
    $draw = Draw::create(['eligible_count' => 2]);

    $this->postJson(route('draw.pick', $draw))->assertOk();
    $second = $this->postJson(route('draw.pick', $draw))->assertOk()->json();
    expect($second['complete'])->toBeTrue();
    expect($second['maxWinners'])->toBe(2);

    $this->postJson(route('draw.pick', $draw))->assertStatus(422);
});

it('treats leads sharing a phone as one person in the pool', function () {
    Lead::factory()->create(['quiz_attempt_id' => null, 'name' => 'Same One', 'phone' => '0771111111']);
    Lead::factory()->create(['quiz_attempt_id' => null, 'name' => 'Same Two', 'phone' => '0771111111']);
    Lead::factory()->create(['quiz_attempt_id' => null, 'name' => 'Other', 'phone' => '0772222222']);

    $this->get(route('draw.index'))
        ->assertInertia(fn ($page) => $page->where('eligibleCount', 2));
});

it('starts a separate draw on "draw again" and keeps prior winners intact', function () {
    seedLeads(5);

    $firstId = $this->postJson(route('draw.start'))->json('drawId');
    $this->postJson(route('draw.pick', ['draw' => $firstId]))->assertOk();

    $secondId = $this->postJson(route('draw.start'))->json('drawId');

    expect($secondId)->not->toBe($firstId);
    expect(Draw::count())->toBe(2);
    expect(DrawWinner::query()->where('draw_id', $firstId)->count())->toBe(1);
    expect(DrawWinner::query()->where('draw_id', $secondId)->count())->toBe(0);
});

it('applies throttle middleware to the draw write routes', function () {
    foreach (['draw.start', 'draw.pick'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        expect($route->gatherMiddleware())->toContain('throttle:60,1');
    }
});
