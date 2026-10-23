<?php

namespace App\Support;

// Konversi angka ke terbilang bahasa Indonesia, mis. 12500 → "Dua belas ribu lima ratus rupiah".
class Terbilang
{
    private const HURUF = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function rupiah(int $angka): string
    {
        return ucfirst(trim(preg_replace('/\s+/', ' ', self::angka($angka)))).' rupiah';
    }

    // Rekursif, hasilnya bisa berisi spasi ganda (dirapikan di rupiah())
    public static function angka(int $angka): string
    {
        $angka = abs($angka);

        if ($angka < 12) {
            return self::HURUF[$angka];
        } elseif ($angka < 20) {
            return self::angka($angka - 10).' belas';
        } elseif ($angka < 100) {
            return self::angka(intdiv($angka, 10)).' puluh '.self::angka($angka % 10);
        } elseif ($angka < 200) {
            return 'seratus '.self::angka($angka - 100);
        } elseif ($angka < 1000) {
            return self::angka(intdiv($angka, 100)).' ratus '.self::angka($angka % 100);
        } elseif ($angka < 2000) {
            return 'seribu '.self::angka($angka - 1000);
        } elseif ($angka < 1000000) {
            return self::angka(intdiv($angka, 1000)).' ribu '.self::angka($angka % 1000);
        } elseif ($angka < 1000000000) {
            return self::angka(intdiv($angka, 1000000)).' juta '.self::angka($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            return self::angka(intdiv($angka, 1000000000)).' milyar '.self::angka($angka % 1000000000);
        }

        return self::angka(intdiv($angka, 1000000000000)).' triliun '.self::angka($angka % 1000000000000);
    }
}
