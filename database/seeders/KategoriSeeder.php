<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            'Parkir',
            'Makan / konsumsi',
            'Alat tulis kantor',
            'Transportasi',
            'Lain-lain',
        ];

        foreach ($kategori as $nama) {
            Kategori::firstOrCreate(['nama' => $nama]);
        }
    }
}