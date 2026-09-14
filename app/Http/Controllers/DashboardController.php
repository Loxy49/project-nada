<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Nota;
use App\Models\NotaItem;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $ringkasan = [
                'total_bulan_ini' => Nota::whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->sum('nominal'),
                'total_nota' => Nota::count(),
                'nota_bulan_ini' => Nota::whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->count(),
            ];
            $terbaru = Nota::with(['pengaju', 'kategori'])->latest()->take(5)->get();
            $perKategori = $this->hitungPerKategori();
            $trenBulanan = $this->hitungTrenBulanan();
            $kategoriTerbesar = $this->hitungKategoriTerbesarBulanIni();

            return view('dashboard.admin', compact('ringkasan', 'terbaru', 'perKategori', 'trenBulanan', 'kategoriTerbesar'));
        }

        $ringkasan = [
            'total_bulan_ini' => $user->notaDiajukan()->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->sum('nominal'),
            'total_nota' => $user->notaDiajukan()->count(),
            'nota_bulan_ini' => $user->notaDiajukan()->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->count(),
        ];
        $notaSaya = $user->notaDiajukan()->with('kategori')->latest()->take(5)->get();

        return view('dashboard.karyawan', compact('ringkasan', 'notaSaya'));
    }

    // Total pengeluaran per kategori BULAN INI (dihitung dari item), buat grafik donut di dashboard
    private function hitungPerKategori(?int $userId = null): array
    {
        $hasil = NotaItem::rekapPerKategori(now(), $userId);

        return [
            'labels' => $hasil->pluck('nama')->toArray(),
            'data' => $hasil->pluck('total')->map(fn ($t) => (float) $t)->toArray(),
        ];
    }

    // Total nominal nota per bulan, 6 bulan terakhir, buat grafik garis trend pengeluaran
    private function hitungTrenBulanan(?int $userId = null, int $jumlahBulan = 6): array
    {
        $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $labels = [];
        $data = [];

        for ($i = $jumlahBulan - 1; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);

            $query = Nota::whereYear('tanggal', $bulan->year)
                ->whereMonth('tanggal', $bulan->month);

            if ($userId) {
                $query->where('pengaju_id', $userId);
            }

            $labels[] = $namaBulan[$bulan->month - 1].' '.$bulan->format('y');
            $data[] = (float) $query->sum('nominal');
        }

        return compact('labels', 'data');
    }

    // Kategori dengan total pengeluaran terbesar bulan berjalan (dihitung dari item)
    private function hitungKategoriTerbesarBulanIni(?int $userId = null): ?array
    {
        $hasil = NotaItem::rekapPerKategori(now(), $userId);

        if ($hasil->isEmpty()) {
            return null;
        }

        $totalBulanIni = $hasil->sum('total');
        $terbesar = $hasil->first();

        return [
            'nama' => $terbesar->nama ?? '-',
            'total' => (float) $terbesar->total,
            'persen' => $totalBulanIni > 0 ? round(($terbesar->total / $totalBulanIni) * 100) : 0,
        ];
    }
}
