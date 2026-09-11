<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('notas');
            $table->foreignId('diubah_oleh')->constrained('users');
            $table->string('status_lama', 20)->nullable();
            $table->string('status_baru', 20);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_statuses');
    }
};
