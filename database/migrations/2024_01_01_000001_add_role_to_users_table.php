<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jalankan setelah migration bawaan Laravel (create_users_table).
// Menambahkan kolom role & departemen ke tabel users default.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['karyawan', 'admin'])->default('karyawan')->after('email');
            $table->string('departemen')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'departemen']);
        });
    }
};
