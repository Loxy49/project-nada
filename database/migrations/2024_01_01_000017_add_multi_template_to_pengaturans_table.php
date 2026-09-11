<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            // "nama" = label template ("Default", "Formal Kantor", dst), beda dari "nama_toko"
            if (! Schema::hasColumn('pengaturans', 'nama')) {
                $table->string('nama')->default('Default')->after('id');
            }
            if (! Schema::hasColumn('pengaturans', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('warna_aksen');
            }
        });

        // Pastikan selalu ada tepat satu template yang jadi default.
        if (DB::table('pengaturans')->where('is_default', true)->doesntExist()) {
            $pertama = DB::table('pengaturans')->orderBy('id')->first();
            if ($pertama) {
                DB::table('pengaturans')->where('id', $pertama->id)->update(['is_default' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            if (Schema::hasColumn('pengaturans', 'nama')) {
                $table->dropColumn('nama');
            }
            if (Schema::hasColumn('pengaturans', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
