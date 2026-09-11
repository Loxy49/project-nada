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
            $table->string('nama')->default('Default')->after('id');
            $table->enum('gaya_default', ['formal', 'struk'])->default('struk')->after('teks_footer');
            $table->boolean('is_default')->default(false)->after('gaya_default');
        });

        // Baris pengaturan yang sudah ada (singleton lama) otomatis jadi template default,
        // biar nota-nota lama tetap kepake template ini tanpa perlu setting ulang.
        DB::table('pengaturans')->orderBy('id')->limit(1)->update([
            'nama' => 'Default',
            'is_default' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn(['nama', 'gaya_default', 'is_default']);
        });
    }
};
