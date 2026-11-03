<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // so_khoan NULL is the article-level vector; otherwise it is that clause's vector.
        Schema::create('law_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dieu_luat_id');
            $table->unsignedInteger('so_khoan')->nullable();
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
