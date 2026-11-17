<?php

namespace App\Http\Controllers;

use App\Models\NotaLog;
use App\Models\User;
use Illuminate\Http\Request;

// Log Aktivitas: jejak audit nota (ubah, hapus, pulihkan) untuk admin/accounting.
// Rutenya dijaga middleware can:admin di routes/web.php.
// Secara default aksi "Dibuat" disembunyikan: setiap nota baru otomatis punya satu catatan "Dibuat",
// dan informasinya sudah ada di Daftar Nota, jadi cuma menuh-menuhin log.
class LogNotaController extends Controller
{
    public function index(Request $request)
    {
        $query = NotaLog::with('user')->latest('id');

        $aksi = $request->input('aksi');
        if ($aksi === 'semua') {
            // tampilkan semua aksi, termasuk "Dibuat"
        } elseif ($aksi && array_key_exists($aksi, NotaLog::LABEL)) {
            $query->where('aksi', $aksi);
        } else {
            $query->where('aksi', '!=', 'buat');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('dari') && strtotime($request->dari) !== false) {
            $query->whereDate('created_at', '>=', $request->dari);
        }

        if ($request->filled('sampai') && strtotime($request->sampai) !== false) {
            $query->whereDate('created_at', '<=', $request->sampai);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Pilihan filter pengguna: hanya yang pernah tercatat di log.
        $pengguna = User::whereIn('id', NotaLog::query()->select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('lognota.index', compact('logs', 'pengguna'));
    }
}
