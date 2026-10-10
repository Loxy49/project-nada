<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// Penguncian periode (bulan) oleh tim accounting.
// Nota yang tanggalnya jatuh di bulan terkunci tidak bisa ditambah, diubah, dihapus, atau dipulihkan
// (berlaku untuk semua orang termasuk admin) sampai kuncinya dibuka dengan alasan tercatat.
class PeriodeKunci extends Model
{
    protected $table = 'periode_kuncis';

    protected $fillable = [
        'tahun', 'bulan', 'dikunci_oleh', 'dikunci_pada', 'catatan',
        'dibuka_oleh', 'dibuka_pada', 'alasan_buka',
    ];

    protected $casts = [
        'dikunci_pada' => 'datetime',
        'dibuka_pada' => 'datetime',
    ];

    private const CACHE_KEY = 'periode.kunci.aktif';

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    // Daftar kunci aktif disimpan di cache array (hidup selama satu request), jadi halaman riwayat
    // yang menampilkan banyak nota tidak menembak database berulang kali. Dibersihkan tiap kunci berubah.
    protected static function booted(): void
    {
        $bersihkan = fn () => Cache::store('array')->forget(self::CACHE_KEY);

        static::saved($bersihkan);
        static::deleted($bersihkan);
    }

    // Apakah tanggal ini jatuh di bulan yang sedang terkunci?
    public static function terkunci($tanggal): bool
    {
        return in_array(Carbon::parse($tanggal)->format('Y-m'), self::kunciAktif(), true);
    }

    // Daftar bulan terkunci aktif, format 'Y-m'
    public static function kunciAktif(): array
    {
        return Cache::store('array')->rememberForever(self::CACHE_KEY, function () {
            return static::whereNull('dibuka_pada')->get()
                ->map(fn ($k) => sprintf('%04d-%02d', $k->tahun, $k->bulan))
                ->all();
        });
    }

    public static function labelBulan(int $tahun, int $bulan): string
    {
        return (self::NAMA_BULAN[$bulan] ?? $bulan).' '.$tahun;
    }

    public static function labelDariTanggal($tanggal): string
    {
        $t = Carbon::parse($tanggal);

        return self::labelBulan($t->year, $t->month);
    }

    public function getLabelAttribute(): string
    {
        return self::labelBulan($this->tahun, $this->bulan);
    }

    public function pengunci()
    {
        return $this->belongsTo(User::class, 'dikunci_oleh');
    }

    public function pembuka()
    {
        return $this->belongsTo(User::class, 'dibuka_oleh');
    }
}
