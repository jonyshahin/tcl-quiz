<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Parses the plain-text question bank format into structured question data.
 *
 * Expected format, repeated per question (blocks separated by blank lines):
 *
 *   <prompt>
 *
 *   A) <option A>
 *   B) <option B>
 *   C) <option C>
 *   D) <option D>
 *
 *   <correct answer line — repeats the full correct option, e.g. "A) ...">
 *
 * The final block may omit a trailing blank line. Malformed blocks are skipped
 * with a logged warning rather than aborting the whole parse.
 */
class QuestionBankParser
{
    /**
     * @return array<int, array{prompt: string, options: array<int, array{label: string, text: string, is_correct: bool}>}>
     */
    public function parseFile(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            Log::warning("QuestionBankParser: file not found or unreadable at [{$path}].");

            return [];
        }

        return $this->parse((string) file_get_contents($path));
    }

    /**
     * @return array<int, array{prompt: string, options: array<int, array{label: string, text: string, is_correct: bool}>}>
     */
    public function parse(string $content): array
    {
        $chunks = $this->splitIntoChunks($content);
        $questions = [];

        foreach ($chunks as $index => $chunk) {
            $optionLines = $this->extractOptionLines($chunk);

            // A chunk is an "options block" when it holds two or more labelled option
            // lines. The chunk before it is the prompt; the chunk after it is the
            // correct-answer line. Single-line correct/prompt chunks are never matched here.
            if (count($optionLines) < 2) {
                continue;
            }

            $prompt = trim((string) ($chunks[$index - 1] ?? ''));
            $correctChunk = trim((string) ($chunks[$index + 1] ?? ''));

            $question = $this->buildQuestion($prompt, $optionLines, $correctChunk, $index);

            if ($question !== null) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * Split text into non-empty, blank-line-delimited chunks (line endings normalized).
     *
     * @return array<int, string>
     */
    private function splitIntoChunks(string $content): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $rawChunks = preg_split('/\n[ \t]*\n/', trim($normalized)) ?: [];

        $chunks = [];
        foreach ($rawChunks as $chunk) {
            $trimmed = trim($chunk);
            if ($trimmed !== '') {
                $chunks[] = $trimmed;
            }
        }

        return $chunks;
    }

    /**
     * Extract labelled option lines (e.g. "A) foo") from a chunk.
     *
     * @return array<int, array{label: string, text: string}>
     */
    private function extractOptionLines(string $chunk): array
    {
        $options = [];

        foreach (preg_split('/\n/', $chunk) ?: [] as $line) {
            if (preg_match('/^\s*([A-Da-d])[\)\.\-:]\s*(.+?)\s*$/u', $line, $m) === 1) {
                $options[] = [
                    'label' => strtoupper($m[1]),
                    'text' => trim($m[2]),
                ];
            }
        }

        return $options;
    }

    /**
     * @param  array<int, array{label: string, text: string}>  $optionLines
     * @return array{prompt: string, options: array<int, array{label: string, text: string, is_correct: bool}>}|null
     */
    private function buildQuestion(string $prompt, array $optionLines, string $correctChunk, int $index): ?array
    {
        if ($prompt === '') {
            Log::warning("QuestionBankParser: skipped block at chunk #{$index} — missing prompt.");

            return null;
        }

        if (count($optionLines) !== 4) {
            Log::warning("QuestionBankParser: skipped question \"{$prompt}\" — expected 4 options, got ".count($optionLines).'.');

            return null;
        }

        $correctLabel = $this->resolveCorrectLabel($correctChunk, $optionLines);

        if ($correctLabel === null) {
            Log::warning("QuestionBankParser: skipped question \"{$prompt}\" — could not match the correct answer.");

            return null;
        }

        $options = [];
        foreach ($optionLines as $option) {
            $options[] = [
                'label' => $option['label'],
                'text' => $option['text'],
                'is_correct' => $option['label'] === $correctLabel,
            ];
        }

        return [
            'prompt' => $prompt,
            'options' => $options,
        ];
    }

    /**
     * Resolve which option label the correct-answer line refers to.
     * Prefers the A)/B)/C)/D) prefix, then falls back to an exact text match.
     *
     * @param  array<int, array{label: string, text: string}>  $optionLines
     */
    private function resolveCorrectLabel(string $correctChunk, array $optionLines): ?string
    {
        if ($correctChunk === '') {
            return null;
        }

        // Primary: match by the leading letter prefix.
        if (preg_match('/^\s*([A-Da-d])[\)\.\-:]/u', $correctChunk, $m) === 1) {
            $label = strtoupper($m[1]);
            foreach ($optionLines as $option) {
                if ($option['label'] === $label) {
                    return $label;
                }
            }
        }

        // Fallback: match by exact (case-insensitive) option text, with or without prefix.
        $needle = preg_replace('/^\s*[A-Da-d][\)\.\-:]\s*/u', '', $correctChunk) ?? $correctChunk;
        $needle = mb_strtolower(trim($needle));

        foreach ($optionLines as $option) {
            if (mb_strtolower($option['text']) === $needle) {
                return $option['label'];
            }
        }

        return null;
    }
}
