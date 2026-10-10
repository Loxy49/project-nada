<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Satu baris = satu kali penguncian bulan. Dibuka lagi tidak menghapus baris, hanya mengisi kolom dibuka_*,
    // jadi riwayat kunci/buka tetap tersimpan sebagai jejak audit. Kunci aktif = dibuka_pada kosong.
    public function up(): void
    {
        Schema::create('periode_kuncis', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');

            $table->foreignId('dikunci_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikunci_pada')->useCurrent();
            $table->string('catatan')->nullable();

            $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibuka_pada')->nullable();
            $table->string('alasan_buka')->nullable();

            $table->timestamps();

            $table->index(['tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_kuncis');
    }
};
