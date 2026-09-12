<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nota extends Model
{
    use SoftDeletes;

    // Foto bukti disimpan di disk private (storage/app/private), tidak bisa dibuka lewat /storage.
    // Satu-satunya jalan melihatnya adalah route nota.bukti yang mengecek login dan hak akses.
    public const DISK_BUKTI = 'local';

    protected $fillable = [
        'no_nota', 'pengaju_id', 'kasir', 'metode_bayar', 'no_pelanggan', 'kategori_id', 'tanggal',
        'nominal', 'keterangan', 'bukti_file', 'dihapus_oleh', 'alasan_hapus',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'pengaju_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    // Rincian item dalam nota ini (kalau pengajuan berisi beberapa item sekaligus)
    public function items()
    {
        return $this->hasMany(NotaItem::class);
    }

    // Apakah nota ini masuk periode (bulan) yang sudah dikunci accounting?
    public function getTerkunciAttribute(): bool
    {
        return PeriodeKunci::terkunci($this->tanggal);
    }

    public function logs()
    {
        return $this->hasMany(NotaLog::class);
    }

    // Admin yang menghapus nota ini (kalau sedang dalam keadaan terhapus)
    public function penghapus()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh');
    }

    // Boleh diedit oleh user ini?
    // - nota di periode terkunci: tidak boleh siapa pun
    // - admin: boleh
    // - karyawan: hanya nota sendiri, dan hanya sampai N hari setelah dibuat (config pettynota.edit_karyawan_hari)
    public function bisaDiedit(User $user): bool
    {
        if ($this->trashed() || $this->terkunci) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->pengaju_id !== $user->id) {
            return false;
        }

        $batas = now()->subDays((int) config('pettynota.edit_karyawan_hari', 7))->startOfDay();

        return $this->created_at->gte($batas);
    }
}
