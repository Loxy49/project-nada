<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {
            $table->string('no_pelanggan')->nullable()->after('keterangan');
            $table->string('sn')->nullable()->after('no_pelanggan');
        });
    }

    public function down(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {
            $table->dropColumn(['no_pelanggan', 'sn']);
        });
    }
};
