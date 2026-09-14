<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Pengaturan;
use App\Support\SimpleXlsxWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RekapExportController extends Controller
{
    private const NAMA_BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    // Ambil semua nota di bulan yang dipilih (format ?bulan=YYYY-MM, default bulan berjalan).
    // Dipakai bareng oleh export Excel & PDF biar datanya konsisten.
    private function ambilDataBulan(Request $request): array
    {
        $bulanInput = $request->query('bulan', now()->format('Y-m'));

        try {
            $tanggal = Carbon::createFromFormat('Y-m', $bulanInput)->startOfMonth();
        } catch (\Exception $e) {
            $tanggal = now()->startOfMonth();
        }

        $notas = Nota::with(['kategori', 'pengaju', 'items.kategori'])
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->orderBy('tanggal')
            ->get();

        // Rekap per kategori dihitung dari item, bukan dari kategori nota.
        // Nota lama tanpa item dihitung utuh ke kategori notanya.
        $perKategori = $notas->flatMap(function ($n) {
            if ($n->items->isEmpty()) {
                return [['nama' => $n->kategori->nama ?? '-', 'total' => (float) $n->nominal]];
            }

            return $n->items->map(fn ($i) => [
                'nama' => $i->kategori->nama ?? '-',
                'total' => (float) $i->subtotal,
            ]);
        })->groupBy('nama')->map(fn ($grp) => $grp->sum('total'))->sortDesc();

        return [
            'notas' => $notas,
            'tanggal' => $tanggal,
            'labelBulan' => self::NAMA_BULAN[$tanggal->month - 1].' '.$tanggal->year,
            'total' => $notas->sum('nominal'),
            'perKategori' => $perKategori,
        ];
    }

    public function excel(Request $request)
    {
        $data = $this->ambilDataBulan($request);
        $pengaturan = Pengaturan::aktif();

        $xlsx = new SimpleXlsxWriter('Rekap '.$data['tanggal']->format('M Y'));

        $xlsx->addRow(['Rekap Pengeluaran Kas Kecil — '.($pengaturan->nama_toko ?? 'NADA')], true);
        $xlsx->addRow(['Periode: '.$data['labelBulan']]);
        $xlsx->addRow([]);
        // Satu baris per item, jadi kategori, No. Pelanggan, SN, qty, dan harga satuan terbaca apa adanya.
        $xlsx->addRow(['No', 'Tanggal', 'No. Transaksi', 'Kasir', 'Kategori', 'Metode Bayar', 'No. Pelanggan', 'SN', 'Keterangan', 'Qty', 'Harga Satuan (Rp)', 'Subtotal (Rp)'], true);

        $no = 0;
        foreach ($data['notas'] as $nota) {
            $baris = $nota->items->isNotEmpty() ? $nota->items : collect([null]);

            foreach ($baris as $item) {
                $no++;
                $xlsx->addRow([
                    $no,
                    $nota->tanggal->format('d/m/Y'),
                    $nota->no_nota,
                    $nota->kasir ?: ($nota->pengaju->name ?? '-'),
                    ($item?->kategori->nama ?? $nota->kategori->nama) ?? '-',
                    $nota->metode_bayar ?: '-',
                    $item?->no_pelanggan ?: '-',
                    $item?->sn ?: '-',
                    ($item?->keterangan ?? $nota->keterangan) ?: '-',
                    $item ? (int) $item->qty : 1,
                    (float) ($item?->nominal ?? $nota->nominal),
                    (float) ($item ? $item->subtotal : $nota->nominal),
                ]);
            }
        }

        $xlsx->addRow([]);
        $xlsx->addRow(['', '', '', '', '', '', '', '', '', '', 'TOTAL', (float) $data['total']], true);

        $xlsx->addRow([]);
        $xlsx->addRow(['Rekap per kategori'], true);
        foreach ($data['perKategori'] as $nama => $totalKategori) {
            $xlsx->addRow([$nama, (float) $totalKategori]);
        }

        $namaFile = 'rekap-pengeluaran-'.$data['tanggal']->format('Y-m').'.xlsx';

        return $xlsx->download($namaFile);
    }

    public function pdf(Request $request)
    {
        $data = $this->ambilDataBulan($request);
        $pengaturan = Pengaturan::aktif();

        // Butuh package barryvdh/laravel-dompdf — kalau belum ke-install,
        // jalankan: composer require barryvdh/laravel-dompdf
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rekap.pdf', [
            'notas' => $data['notas'],
            'labelBulan' => $data['labelBulan'],
            'total' => $data['total'],
            'perKategori' => $data['perKategori'],
            'pengaturan' => $pengaturan,
        ])->setPaper('a4', 'portrait');

        $namaFile = 'rekap-pengeluaran-'.$data['tanggal']->format('Y-m').'.pdf';

        return $pdf->download($namaFile);
    }
}
