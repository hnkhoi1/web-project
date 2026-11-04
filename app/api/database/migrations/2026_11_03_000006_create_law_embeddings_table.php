<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // so_khoan 0 is the article-level vector (clause numbers start at 1). Not NULL, because
        // MySQL treats NULLs as distinct and the unique key would not cover article rows.
        Schema::create('law_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dieu_luat_id');
            $table->unsignedInteger('so_khoan')->default(0);
            $table->string('model', 100);
            $table->char('content_hash', 64);
            $table->json('vector');

            $table->foreign('dieu_luat_id')->references('id')->on('dieu_luat')->cascadeOnDelete();
            $table->unique(['dieu_luat_id', 'so_khoan', 'model'], 'uq_law_embeddings');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('law_embeddings');
    }
};
