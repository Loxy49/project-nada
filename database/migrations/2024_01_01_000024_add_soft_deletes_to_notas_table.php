<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nota yang "dihapus" admin tidak hilang dari database: hanya ditandai (deleted_at), lengkap dengan siapa dan alasannya,
    // sehingga bisa dipulihkan dan tetap bisa diaudit. Rincian itemnya sengaja tidak ikut dihapus.
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('dihapus_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alasan_hapus')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dihapus_oleh');
            $table->dropColumn('alasan_hapus');
            $table->dropSoftDeletes();
        });
    }
};
