<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public quiz (full-screen Inertia micro-site)
|--------------------------------------------------------------------------
*/
Route::get('/', [QuizController::class, 'start'])->name('quiz.start');
Route::post('/quiz/start', [QuizController::class, 'begin'])->name('quiz.begin');

Route::get('/quiz/{attempt:session_token}/q/{index}', [QuizController::class, 'question'])
    ->whereNumber('index')
    ->name('quiz.question');

Route::post('/quiz/{attempt:session_token}/answer', [QuizController::class, 'answer'])
    ->name('quiz.answer');

Route::get('/quiz/{attempt:session_token}/result', [QuizController::class, 'result'])
    ->name('quiz.result');

Route::post('/quiz/{attempt:session_token}/lead', [LeadController::class, 'store'])
    ->name('quiz.lead');

/*
|--------------------------------------------------------------------------
| Admin (auth + admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/questions');
    Route::get('leads/export', [AdminLeadController::class, 'export'])->name('leads.export');
    Route::get('leads', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::resource('questions', AdminQuestionController::class)->except('show');
});

/*
|--------------------------------------------------------------------------
| Authenticated landing — send admins to the panel
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('admin.questions.index');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
