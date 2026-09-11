<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            // No. Pelanggan sekarang satu per nota (bukan per item lagi)
            $table->string('no_pelanggan')->nullable()->after('metode_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn('no_pelanggan');
        });
    }
};
