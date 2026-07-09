<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draw_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // Reveal order within the draw: 1, 2, or 3.
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['draw_id', 'position']);
            $table->unique(['draw_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_winners');
    }
};
