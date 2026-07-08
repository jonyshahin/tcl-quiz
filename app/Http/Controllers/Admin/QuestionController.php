<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\AnswerOption;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function index(): Response
    {
        $questions = Question::query()
            ->ordered()
            ->with('answerOptions')
            ->get()
            ->map(fn (Question $q) => $this->transform($q));

        return Inertia::render('admin/questions/index', [
            'questions' => $questions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/questions/create', [
            'nextOrder' => (int) (Question::max('order') ?? -1) + 1,
        ]);
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $question = Question::create($request->safe()->only(['prompt', 'explanation', 'order', 'is_active']));
            $this->syncOptions($question, $request->validated('options'), (int) $request->validated('correct_index'));
        });

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function edit(Question $question): Response
    {
        $question->load('answerOptions');

        return Inertia::render('admin/questions/edit', [
            'question' => $this->transform($question),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        DB::transaction(function () use ($request, $question) {
            $question->update($request->safe()->only(['prompt', 'explanation', 'order', 'is_active']));
            $this->syncOptions($question, $request->validated('options'), (int) $request->validated('correct_index'));
        });

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'Question deleted.');
    }

    /**
     * Replace a question's options with the submitted four, marking exactly one correct.
     *
     * @param  array<int, array{label: string, text: string}>  $options
     */
    private function syncOptions(Question $question, array $options, int $correctIndex): void
    {
        $question->answerOptions()->delete();

        foreach (array_values($options) as $i => $option) {
            $question->answerOptions()->create([
                'label' => $option['label'],
                'text' => $option['text'],
                'is_correct' => $i === $correctIndex,
                'order' => $i,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Question $question): array
    {
        return [
            'id' => $question->id,
            'prompt' => $question->prompt,
            'explanation' => $question->explanation,
            'order' => $question->order,
            'is_active' => $question->is_active,
            'correct_index' => $question->answerOptions->values()->search(fn (AnswerOption $o) => $o->is_correct),
            'options' => $question->answerOptions->map(fn (AnswerOption $o) => [
                'id' => $o->id,
                'label' => $o->label,
                'text' => $o->text,
                'is_correct' => $o->is_correct,
            ])->values(),
        ];
    }
}
