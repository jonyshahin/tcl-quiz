<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $quiz_attempt_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property bool $is_winner
 * @property string $score
 */
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'name',
        'email',
        'phone',
        'is_winner',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * A privacy-safe rendering of the phone number for the public draw screen.
     *
     * Keeps the first 4 and last 2 characters and replaces the middle with
     * bullets (e.g. `07701234589` → `0770•••••89`). Very short numbers keep only
     * the last 2 characters. Returns null when no phone is on file.
     */
    public function maskedPhone(): ?string
    {
        $phone = trim((string) ($this->phone ?? ''));

        if ($phone === '') {
            return null;
        }

        $length = mb_strlen($phone);

        // Too short to keep a 4-char prefix: mask all but the last 2 characters.
        if ($length <= 6) {
            $visible = min(2, $length);
            $hidden = $length - $visible;

            return str_repeat('•', $hidden).mb_substr($phone, $length - $visible);
        }

        $prefix = mb_substr($phone, 0, 4);
        $suffix = mb_substr($phone, $length - 2);
        $hidden = $length - 6;

        return $prefix.str_repeat('•', $hidden).$suffix;
    }
}
