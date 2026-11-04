<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ho_ten', 100);
            $table->string('sdt', 25)->nullable();
            $table->string('email', 255);
            $table->text('noi_dung');
            $table->enum('trang_thai', ['moi', 'da_tra_loi'])->default('moi');
            $table->timestamps();

            $table->index('trang_thai', 'idx_contacts_status');
            $table->index('created_at', 'idx_contacts_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
