<?php

declare(strict_types=1);

use App\Models\Lead;

it('masks a normal phone keeping the first 4 and last 2 digits', function () {
    $lead = new Lead(['phone' => '07701234589']);

    expect($lead->maskedPhone())->toBe('0770•••••89');
});

it('returns null when no phone is on file', function () {
    expect((new Lead(['phone' => null]))->maskedPhone())->toBeNull();
    expect((new Lead(['phone' => '']))->maskedPhone())->toBeNull();
    expect((new Lead(['phone' => '   ']))->maskedPhone())->toBeNull();
});

it('masks all but the last 2 characters for very short phones', function () {
    expect((new Lead(['phone' => '123456']))->maskedPhone())->toBe('••••56');
    expect((new Lead(['phone' => '1234']))->maskedPhone())->toBe('••34');
    expect((new Lead(['phone' => '99']))->maskedPhone())->toBe('99');
});

it('never leaks the middle digits of a normal phone', function () {
    $masked = (new Lead(['phone' => '07701234589']))->maskedPhone();

    expect($masked)->not->toContain('12345');
    expect(mb_strlen((string) $masked))->toBe(mb_strlen('07701234589'));
});
