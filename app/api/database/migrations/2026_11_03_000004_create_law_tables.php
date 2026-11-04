<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chuong', function (Blueprint $table) {
            $table->id();
            $table->string('ten_chuong', 255)->unique();
            $table->unsignedInteger('so_thu_tu')->default(1)->index('idx_chuong_stt');
            $table->text('mo_ta')->nullable();
        });

        // id is the article number itself (123, 134, 174...), not auto-increment.
        Schema::create('dieu_luat', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->foreignId('chuong_id')->constrained('chuong')->restrictOnDelete();
            $table->string('tieu_de', 255);
            $table->text('tom_tat');
            $table->text('tu_khoa');
            $table->text('khung_hinh_phat');
            $table->text('can_cu_boi_thuong')->nullable();
            $table->text('ghi_chu_quyen_loi')->nullable();
            $table->timestamps();
        });

        Schema::create('khoan', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dieu_luat_id');
            $table->unsignedInteger('so_khoan');
            $table->text('dieu_kien')->nullable();
            $table->text('noi_dung');
            $table->text('quyen_loi')->nullable();
            $table->text('tu_khoa')->nullable();
            $table->decimal('ti_le_ton_thuong_min', 5, 2)->nullable();
            $table->decimal('ti_le_ton_thuong_max', 5, 2)->nullable();
            // Unit: million VND. Scale 3 because the source has bounds such as 199.999.
            $table->decimal('gia_tri_tai_san_min', 12, 3)->nullable();
            $table->decimal('gia_tri_tai_san_max', 12, 3)->nullable();
            $table->integer('so_nguoi_chet_min')->nullable();
            $table->integer('so_nguoi_chet_max')->nullable();
            $table->text('cau_hoi_lam_ro')->nullable();

            $table->foreign('dieu_luat_id')->references('id')->on('dieu_luat')->cascadeOnDelete();
            $table->unique(['dieu_luat_id', 'so_khoan'], 'uq_dieu_khoan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khoan');
        Schema::dropIfExists('dieu_luat');
        Schema::dropIfExists('chuong');
    }
};
