<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota', 30)->unique();
            $table->foreignId('pengaju_id')->constrained('users');
            $table->foreignId('kategori_id')->constrained('kategoris');
            $table->date('tanggal');
            $table->decimal('nominal', 12, 2);
            $table->string('keterangan')->nullable();
            $table->string('bukti_file')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
