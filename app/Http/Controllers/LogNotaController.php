<?php

namespace App\Http\Controllers;

use App\Models\NotaLog;
use Illuminate\Http\Request;

// Semua jejak audit nota, untuk admin/accounting. Rutenya dijaga middleware can:admin di routes/web.php.
class LogNotaController extends Controller
{
    public function index(Request $request)
    {
        $query = NotaLog::with('user')->latest('id');

        if ($request->filled('aksi') && array_key_exists($request->aksi, NotaLog::LABEL)) {
            $query->where('aksi', $request->aksi);
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('lognota.index', compact('logs'));
    }
}
