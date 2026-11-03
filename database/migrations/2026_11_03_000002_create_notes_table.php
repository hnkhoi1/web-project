<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('content')->nullable();
            // yellow | green | blue | rose | violet | gray
            $table->string('color', 20)->default('yellow');
            $table->boolean('pinned')->default(false);
            $table->string('source_type', 50)->nullable();
            $table->string('source_ref', 50)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pinned', 'created_at'], 'idx_notes_user_pinned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
