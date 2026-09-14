<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->enum('orientasi', ['potret', 'lanskap'])->default('potret')->after('gaya_default');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('orientasi');
        });
    }
};
