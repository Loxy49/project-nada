<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Jejak audit nota: siapa membuat / mengubah / menghapus / memulihkan nota, kapan, dan kondisi sebelum-sesudahnya.
    // nota_id sengaja tanpa foreign key dan no_nota disalin, supaya jejak tetap ada walau barisnya hilang dari tabel notas.
    public function up(): void
    {
        Schema::create('nota_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nota_id')->index();
            $table->string('no_nota', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 20)->index();
            $table->string('alasan')->nullable();
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_logs');
    }
};
