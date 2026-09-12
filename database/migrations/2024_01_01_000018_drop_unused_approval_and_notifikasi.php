<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fitur approval nota gak jadi dipakai (begitu dibuat, nota langsung final),
        // jadi kolom-kolom ini di tabel notas gak pernah kepakai.
        Schema::table('notas', function (Blueprint $table) {
            if (Schema::hasColumn('notas', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('notas', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('notas', 'status')) {
                $table->dropColumn('status');
            }
        });

        // Tabel log_statuses (histori perubahan status) & notifikasis juga bagian
        // dari fitur approval/notifikasi yang gak jadi dipakai.
        Schema::dropIfExists('log_statuses');
        Schema::dropIfExists('notifikasis');
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
        });

        Schema::create('log_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('notas');
            $table->foreignId('diubah_oleh')->constrained('users');
            $table->string('status_lama', 20)->nullable();
            $table->string('status_baru', 20);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('nota_id')->constrained('notas');
            $table->string('pesan');
            $table->boolean('dibaca')->default(false);
            $table->timestamps();
        });
    }
};
