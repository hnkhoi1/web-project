<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_prefs', function (Blueprint $table) {
            // How the notes page lays out notes: 'grid' (cards) or 'list' (one per row).
            $table->string('notes_view', 10)->default('grid')->after('note_color');
        });
    }

    public function down(): void
    {
        Schema::table('user_prefs', function (Blueprint $table) {
            $table->dropColumn('notes_view');
        });
    }
};
