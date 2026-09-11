<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $fillable = [
        'nama', 'nama_toko', 'logo', 'alamat', 'no_cs', 'teks_footer', 'gaya_default', 'orientasi', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    // Template yang dipakai kalau tidak dipilih spesifik saat cetak.
    // Kalau belum ada template sama sekali, bikin satu default otomatis.
    public static function aktif(): self
    {
        return static::where('is_default', true)->first()
            ?? static::first()
            ?? static::create([
                'nama' => 'Default',
                'nama_toko' => 'NADA',
                'alamat' => 'Jl. Contoh Raya No. 10, Banjarmasin',
                'no_cs' => '0800-1234-5678',
                'teks_footer' => 'Terima kasih. Nota ini dibuat otomatis oleh sistem NADA.',
                'gaya_default' => 'struk',
                'orientasi' => 'potret',
                'is_default' => true,
            ]);
    }

    // Alias, jaga-jaga kalau ada bagian lain yang manggil nama ini.
    public static function ambil(): self
    {
        return static::aktif();
    }

    // Jadikan template ini satu-satunya default (yang lain otomatis bukan default lagi)
    public function jadikanDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
