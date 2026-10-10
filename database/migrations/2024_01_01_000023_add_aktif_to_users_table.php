<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Akun tidak dihapus (nota yang pernah dibuatnya harus tetap punya pengaju), cukup dinonaktifkan.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('departemen');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }
};
