<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_su_hoi_dap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('cau_hoi');
            $table->unsignedInteger('dieu_luat_id')->nullable();
            $table->unsignedInteger('khoan_so')->nullable();
            $table->decimal('diem', 5, 4)->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_lich_su_time');

            $table->foreign('dieu_luat_id')->references('id')->on('dieu_luat')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_hoi_dap');
    }
};
