<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lich_su_hoi_dap', function (Blueprint $table) {
            // The asker's verdict on the answer: 'dung' (correct), 'sai' (wrong) or null (not rated).
            $table->string('danh_gia', 10)->nullable()->after('diem');
            $table->index(['user_id', 'created_at'], 'idx_lich_su_user_time');
        });
    }

    public function down(): void
    {
        Schema::table('lich_su_hoi_dap', function (Blueprint $table) {
            $table->dropIndex('idx_lich_su_user_time');
            $table->dropColumn('danh_gia');
        });
    }
};
