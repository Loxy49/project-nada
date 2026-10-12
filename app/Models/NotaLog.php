<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

// Jejak audit nota. Hanya ditambah, tidak pernah diubah/dihapus lewat aplikasi.
class NotaLog extends Model
{
    public const LABEL = [
        'buat' => 'Dibuat',
        'ubah' => 'Diubah',
        'hapus' => 'Dihapus',
        'pulihkan' => 'Dipulihkan',
    ];

    protected $fillable = ['nota_id', 'no_nota', 'user_id', 'aksi', 'alasan', 'sebelum', 'sesudah'];

    protected $casts = [
        'sebelum' => 'array',
        'sesudah' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAksiLabelAttribute(): string
    {
        return self::LABEL[$this->aksi] ?? $this->aksi;
    }

    // Foto kondisi nota saat ini (header + rincian item) untuk dibandingkan sebelum/sesudah.
    public static function snapshot(Nota $nota): array
    {
        $nota->loadMissing('items.kategori');

        return [
            'tanggal' => $nota->tanggal?->toDateString(),
            'kasir' => $nota->kasir,
            'metode_bayar' => $nota->metode_bayar,
            'total' => (float) $nota->nominal,
            'bukti' => $nota->bukti_file ? basename($nota->bukti_file) : null,
            'items' => $nota->items->map(fn ($i) => [
                'kategori' => $i->kategori->nama ?? '-',
                'keterangan' => $i->keterangan,
                'nominal' => (float) $i->nominal,
                'qty' => (int) $i->qty,
                'no_pelanggan' => $i->no_pelanggan,
                'sn' => $i->sn,
            ])->values()->all(),
        ];
    }

    public static function catat(Nota $nota, string $aksi, ?array $sebelum = null, ?array $sesudah = null, ?string $alasan = null): self
    {
        return static::create([
            'nota_id' => $nota->id,
            'no_nota' => $nota->no_nota,
            'user_id' => Auth::id(),
            'aksi' => $aksi,
            'alasan' => $alasan,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
        ]);
    }

    // Daftar perubahan yang mudah dibaca, mis. "Total: Rp10.000 → Rp12.000"
    public function perubahan(): array
    {
        $a = $this->sebelum ?? [];
        $b = $this->sesudah ?? [];

        $label = [
            'tanggal' => 'Tanggal', 'kasir' => 'Kasir', 'metode_bayar' => 'Metode bayar',
            'total' => 'Total', 'bukti' => 'Foto bukti',
        ];

        $hasil = [];
        foreach ($label as $kunci => $nama) {
            if (($a[$kunci] ?? null) != ($b[$kunci] ?? null)) {
                $hasil[] = $nama.': '.self::tampil($kunci, $a[$kunci] ?? null).' → '.self::tampil($kunci, $b[$kunci] ?? null);
            }
        }

        if (($a['items'] ?? []) != ($b['items'] ?? [])) {
            $hasil[] = 'Rincian item: '.self::ringkasItem($a['items'] ?? []).' → '.self::ringkasItem($b['items'] ?? []);
        }

        return $hasil;
    }

    private static function tampil(string $kunci, mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }

        return $kunci === 'total' ? 'Rp'.number_format((float) $nilai, 0, ',', '.') : (string) $nilai;
    }

    private static function ringkasItem(array $items): string
    {
        if (! $items) {
            return '-';
        }

        return collect($items)->map(function ($i) {
            $teks = ($i['keterangan'] ?: $i['kategori']).' '.$i['qty'].'×Rp'.number_format((float) $i['nominal'], 0, ',', '.');
            foreach (['no_pelanggan' => 'No. Pelanggan', 'sn' => 'SN'] as $k => $nama) {
                if (! empty($i[$k])) {
                    $teks .= " ({$nama} {$i[$k]})";
                }
            }

            return $teks;
        })->implode('; ');
    }
}
