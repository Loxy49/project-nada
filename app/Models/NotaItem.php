<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotaItem extends Model
{
    protected $fillable = ['nota_id', 'kategori_id', 'keterangan', 'no_pelanggan', 'sn', 'nominal', 'qty'];

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    // Total pengeluaran per kategori, dihitung dari ITEM (bukan dari kategori nota),
    // supaya nota berisi beberapa kategori terbagi dengan benar.
    // $bulan = filter satu bulan (null = semua waktu), $userId = filter pengaju.
    public static function rekapPerKategori(?Carbon $bulan = null, ?int $userId = null): Collection
    {
        return static::query()
            ->join('notas', 'notas.id', '=', 'nota_items.nota_id')
            ->join('kategoris', 'kategoris.id', '=', 'nota_items.kategori_id')
            ->whereNull('notas.deleted_at')
            ->when($bulan, fn ($q) => $q->whereYear('notas.tanggal', $bulan->year)
                                         ->whereMonth('notas.tanggal', $bulan->month))
            ->when($userId, fn ($q) => $q->where('notas.pengaju_id', $userId))
            ->groupBy('kategoris.id', 'kategoris.nama')
            ->selectRaw('kategoris.nama as nama, SUM(nota_items.nominal * nota_items.qty) as total')
            ->orderByDesc('total')
            ->get();
    }

    // Subtotal baris ini = harga satuan (nominal) x qty
    public function getSubtotalAttribute()
    {
        return $this->nominal * $this->qty;
    }
}
