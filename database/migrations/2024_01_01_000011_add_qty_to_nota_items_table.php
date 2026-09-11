<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {
            // 'nominal' dipakai sebagai harga satuan, subtotal = nominal * qty
            $table->unsignedInteger('qty')->default(1)->after('nominal');
        });
    }

    public function down(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
