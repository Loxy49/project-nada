<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    // Foto bukti dulu disimpan di disk public, jadi bisa dibuka lewat /storage/bukti-nota/... tanpa login.
    // Pindahkan ke disk private (storage/app/private); path di kolom bukti_file tetap sama, jadi data nota tidak perlu diubah.
    public function up(): void
    {
        $this->pindahkan('public', 'local');
    }

    public function down(): void
    {
        $this->pindahkan('local', 'public');
    }

    private function pindahkan(string $dari, string $ke): void
    {
        $asal = Storage::disk($dari);
        $tujuan = Storage::disk($ke);

        foreach ($asal->files('bukti-nota') as $path) {
            // Hapus file asal hanya kalau salinannya berhasil ditulis.
            if ($tujuan->exists($path) || $tujuan->put($path, $asal->get($path))) {
                $asal->delete($path);
            }
        }
    }
};
