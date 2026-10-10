<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\PeriodeKunci;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Kunci periode untuk tim accounting (admin). Rutenya sudah dijaga middleware can:admin di routes/web.php.
class PeriodeController extends Controller
{
    // Daftar bulan (maks. 24 bulan terakhir) beserta status kunci, dan riwayat kunci/buka
    public function index()
    {
        $bulanIni = now()->startOfMonth();

        $tertua = Nota::min('tanggal');
        $awal = $tertua ? Carbon::parse($tertua)->startOfMonth() : $bulanIni->copy();
        // Bulan terkunci selalu ikut tampil (walau belum ada nota di bulan itu), supaya kuncinya bisa dibuka.
        $kunciTertua = PeriodeKunci::whereNull('dibuka_pada')->orderBy('tahun')->orderBy('bulan')->first();
        if ($kunciTertua) {
            $bulanKunci = Carbon::create($kunciTertua->tahun, $kunciTertua->bulan, 1)->startOfMonth();
            if ($bulanKunci->lt($awal)) {
                $awal = $bulanKunci;
            }
        }

        if ($awal->lt($bulanIni->copy()->subMonths(23))) {
            $awal = $bulanIni->copy()->subMonths(23);
        }

        $perBulan = Nota::where('tanggal', '>=', $awal->toDateString())
            ->get(['tanggal', 'nominal'])
            ->groupBy(fn ($n) => $n->tanggal->format('Y-m'));

        $kunciAktif = PeriodeKunci::with('pengunci')->whereNull('dibuka_pada')->get()
            ->keyBy(fn ($k) => sprintf('%04d-%02d', $k->tahun, $k->bulan));

        $baris = [];
        for ($b = $bulanIni->copy(); $b->gte($awal); $b->subMonth()) {
            $key = $b->format('Y-m');
            $notas = $perBulan->get($key, collect());

            $baris[] = [
                'key' => $key,
                'label' => PeriodeKunci::labelBulan($b->year, $b->month),
                'jumlah' => $notas->count(),
                'total' => $notas->sum('nominal'),
                'kunci' => $kunciAktif->get($key),
                // Bulan berjalan belum boleh dikunci: nota hari ini masih akan masuk.
                'bisa_dikunci' => $b->lt($bulanIni),
            ];
        }

        $riwayat = PeriodeKunci::with(['pengunci', 'pembuka'])->latest('id')->limit(20)->get();

        return view('periode.index', compact('baris', 'riwayat'));
    }

    // Kunci satu bulan
    public function kunci(Request $request)
    {
        $data = $request->validate([
            'bulan' => ['required', 'date_format:Y-m'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $bulan = Carbon::createFromFormat('Y-m-d', $data['bulan'].'-01')->startOfDay();

        if ($bulan->gte(now()->startOfMonth())) {
            return back()->with('error', 'Bulan berjalan belum dapat dikunci. Kunci periode setelah bulan tersebut selesai.');
        }

        if (PeriodeKunci::terkunci($bulan)) {
            return back()->with('error', 'Periode '.PeriodeKunci::labelDariTanggal($bulan).' sudah terkunci.');
        }

        PeriodeKunci::create([
            'tahun' => $bulan->year,
            'bulan' => $bulan->month,
            'dikunci_oleh' => Auth::id(),
            'dikunci_pada' => now(),
            'catatan' => $data['catatan'] ?? null,
        ]);

        return back()->with('success', 'Periode '.PeriodeKunci::labelDariTanggal($bulan).' berhasil dikunci.');
    }

    // Buka kunci (wajib alasan, tercatat di riwayat)
    public function buka(Request $request, PeriodeKunci $periode)
    {
        $data = $request->validate(
            ['alasan' => ['required', 'string', 'min:5', 'max:255']],
            ['alasan.required' => 'Alasan membuka kunci wajib diisi.', 'alasan.min' => 'Alasan terlalu singkat (minimal 5 karakter).']
        );

        if ($periode->dibuka_pada !== null) {
            return back()->with('error', 'Kunci periode ini sudah dibuka sebelumnya.');
        }

        $periode->update([
            'dibuka_oleh' => Auth::id(),
            'dibuka_pada' => now(),
            'alasan_buka' => $data['alasan'],
        ]);

        return back()->with('success', 'Kunci periode '.$periode->label.' dibuka. Kunci kembali periode ini setelah koreksi selesai.');
    }
}
