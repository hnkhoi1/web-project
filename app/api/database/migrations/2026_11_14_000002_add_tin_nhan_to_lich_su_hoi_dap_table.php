<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lich_su_hoi_dap', function (Blueprint $table) {
            // What the user actually typed. `cau_hoi` keeps the question merged with earlier turns,
            // which is what the answer is computed from. Older rows have no `tin_nhan`.
            $table->text('tin_nhan')->nullable()->after('cau_hoi');
        });
    }

    public function down(): void
    {
        Schema::table('lich_su_hoi_dap', function (Blueprint $table) {
            $table->dropColumn('tin_nhan');
        });
    }
};
