<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sengaja TIDAK membuat akun contoh (test@example.com, password "password") karena berbahaya kalau
        // ikut terbawa ke server. Buat admin pertama dengan: php artisan nada:buat-admin
        $this->call(KategoriSeeder::class);
    }
}
