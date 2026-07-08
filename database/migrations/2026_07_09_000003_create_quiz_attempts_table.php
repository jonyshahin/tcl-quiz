<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_token')->unique();
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->boolean('is_winner')->default(false);
            // Map of question_id => { selected_option_id, is_correct } recorded server-side.
            $table->json('answers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('session_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
