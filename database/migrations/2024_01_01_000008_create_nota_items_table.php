<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('notas')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategoris');
            $table->string('keterangan')->nullable();
            $table->decimal('nominal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_items');
    }
};
