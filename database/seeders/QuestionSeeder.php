<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Services\QuestionBankParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the question bank from resources/brand/questions and answers.txt.
 *
 * Idempotent: questions are matched on their prompt and their options are
 * upserted on the (question, label) pair, so re-running never duplicates data.
 */
class QuestionSeeder extends Seeder
{
    public function run(QuestionBankParser $parser): void
    {
        $path = resource_path('brand/questions and answers.txt');
        $parsed = $parser->parseFile($path);

        if ($parsed === []) {
            $this->command?->warn("QuestionSeeder: no questions parsed from [{$path}].");

            return;
        }

        DB::transaction(function () use ($parsed) {
            foreach ($parsed as $order => $data) {
                $question = Question::updateOrCreate(
                    ['prompt' => $data['prompt']],
                    ['order' => $order, 'is_active' => true],
                );

                $keptLabels = [];

                foreach ($data['options'] as $optionOrder => $option) {
                    AnswerOption::updateOrCreate(
                        ['question_id' => $question->id, 'label' => $option['label']],
                        [
                            'text' => $option['text'],
                            'is_correct' => $option['is_correct'],
                            'order' => $optionOrder,
                        ],
                    );
                    $keptLabels[] = $option['label'];
                }

                // Remove any options that no longer appear in the source file.
                $question->answerOptions()
                    ->whereNotIn('label', $keptLabels)
                    ->delete();
            }
        });

        $this->command?->info('QuestionSeeder: seeded '.count($parsed).' question(s).');
    }
}
