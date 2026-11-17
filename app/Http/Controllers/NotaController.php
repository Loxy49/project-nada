<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotaRequest;
use App\Models\Kategori;
use App\Models\Nota;
use App\Models\NotaLog;
use App\Models\PeriodeKunci;
use App\Models\Pengaturan;
use App\Support\Terbilang;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NotaController extends Controller
{
    // Form pengajuan nota baru (karyawan)
    public function create()
    {
        $kategori = Kategori::orderBy('nama')->get();

        return view('nota.create', compact('kategori'));
    }

    // Simpan nota baru yang dibuat karyawan (bisa berisi beberapa item sekaligus,
    // tiap item bisa punya qty > 1 kalau barang/transaksinya sama diulang).
    // Tidak ada alur approval — begitu disimpan, nota langsung final.
    public function store(NotaRequest $request)
    {
        $validated = $request->validated();

        $buktiFile = null;
        if ($request->hasFile('bukti_file')) {
            $buktiFile = $request->file('bukti_file')->store('bukti-nota', Nota::DISK_BUKTI);
        }

        $items = $request->items();
        $total = $request->total();

        // Nota + rincian item disimpan dalam satu transaksi: kalau salah satu gagal, semuanya dibatalkan.
        // Kalau dua orang menyimpan bersamaan dan nomor nota kebetulan bentrok (unique), ulangi dengan nomor baru.
        // No. Pelanggan & SN cuma keisi kalau itemnya pembelian pulsa/token/PDAM, ditulis manual
        // karena itu identitas dari pihak luar (PLN/provider).
        $nota = null;
        for ($coba = 1; $coba <= 5; $coba++) {
            try {
                $nota = DB::transaction(function () use ($validated, $items, $total, $buktiFile) {
                    $nota = Nota::create([
                        'no_nota' => $this->generateNoNota(),
                        'pengaju_id' => Auth::id(),
                        // Kasir wajib tampil di nota; kalau tidak diisi, pakai nama akun yang login.
                        'kasir' => $validated['kasir'] ?: Auth::user()->name,
                        'metode_bayar' => $validated['metode_bayar'] ?? null,
                        'kategori_id' => $items[0]['kategori_id'],
                        'tanggal' => $validated['tanggal'],
                        'nominal' => $total,
                        'keterangan' => $this->ringkasKeterangan($items),
                        'bukti_file' => $buktiFile,
                    ]);

                    foreach ($items as $item) {
                        $nota->items()->create($item);
                    }

                    NotaLog::catat($nota, 'buat', null, NotaLog::snapshot($nota));

                    return $nota;
                });
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($coba === 5) {
                    $this->hapusBuktiYatim($buktiFile);
                    throw $e;
                }
            } catch (\Throwable $e) {
                $this->hapusBuktiYatim($buktiFile);
                throw $e;
            }
        }

        return redirect()->route('nota.show', $nota)
            ->with('success', "Nota {$nota->no_nota} berhasil dibuat.");
    }

    // Detail satu nota
    public function show(Nota $nota)
    {
        $admin = Auth::user()->isAdmin();

        // Nota yang sudah dihapus hanya boleh dilihat admin (untuk audit atau dipulihkan).
        abort_if($nota->trashed() && ! $admin, 404);

        $this->authorizeAkses($nota);

        $nota->load(['pengaju', 'kategori', 'items.kategori', 'penghapus']);
        $logs = $admin ? $nota->logs()->with('user')->latest('id')->get() : collect();

        return view('nota.show', compact('nota', 'logs'));
    }

    // Tampilkan foto bukti lewat aplikasi (wajib login dan sesuai hak akses nota).
    // File ada di disk private, jadi tidak bisa dibuka langsung lewat /storage tanpa login.
    public function bukti(Nota $nota)
    {
        // Nota terhapus hanya boleh dilihat admin (sama seperti halaman detailnya).
        abort_if($nota->trashed() && ! Auth::user()->isAdmin(), 404);

        $this->authorizeAkses($nota);

        abort_unless($nota->bukti_file && Storage::disk(Nota::DISK_BUKTI)->exists($nota->bukti_file), 404);

        return Storage::disk(Nota::DISK_BUKTI)->response($nota->bukti_file, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    // Form edit nota (pemilik selama masih dalam batas hari dan periode belum diarsipkan, atau admin selama periode belum dikunci)
    public function edit(Nota $nota)
    {
        $this->authorizeEdit($nota);

        $kategori = Kategori::orderBy('nama')->get();
        $nota->load('items');

        return view('nota.edit', compact('nota', 'kategori'));
    }

    // Simpan perubahan nota: rincian item lama dihapus, diganti yang baru dari form
    public function update(Nota $nota)
    {
        // Hak akses dicek dulu, baru form divalidasi (NotaRequest langsung memvalidasi saat di-resolve),
        // supaya user tanpa akses dapat 403, bukan pesan validasi.
        $this->authorizeEdit($nota);

        $request = app(NotaRequest::class);
        $validated = $request->validated();

        $buktiLama = $nota->bukti_file;
        $buktiFile = $buktiLama;
        if ($request->hasFile('bukti_file')) {
            $buktiFile = $request->file('bukti_file')->store('bukti-nota', Nota::DISK_BUKTI);
        }

        $items = $request->items();
        $total = $request->total();

        // Update header + sinkron item dalam satu transaksi (hapus yang lama, buat ulang dari form).
        // Kondisi sebelum & sesudah dicatat ke jejak audit (hanya kalau memang ada yang berubah).
        $sebelum = NotaLog::snapshot($nota);

        try {
            $this->simpanPerubahan($nota, $validated, $items, $total, $buktiFile, $sebelum);
        } catch (\Throwable $e) {
            // Transaksi gagal: nota masih memakai foto lama, foto baru yang terlanjur diunggah dibuang.
            if ($buktiFile !== $buktiLama) {
                $this->hapusBuktiYatim($buktiFile);
            }
            throw $e;
        }

        // Foto lama dihapus setelah transaksi berhasil (kalau gagal, nota masih memakai foto lama).
        // Jejak audit hanya mencatat nama filenya, jadi foto yang sudah diganti tidak dipakai lagi di mana pun.
        if ($buktiLama && $buktiLama !== $buktiFile) {
            Storage::disk(Nota::DISK_BUKTI)->delete($buktiLama);
        }

        return redirect()->route('nota.show', $nota)
            ->with('success', "Nota {$nota->no_nota} berhasil diperbarui.");
    }

    private function simpanPerubahan(Nota $nota, array $validated, array $items, float $total, ?string $buktiFile, array $sebelum): void
    {
        DB::transaction(function () use ($nota, $validated, $items, $total, $buktiFile, $sebelum) {
            $nota->update([
                'kasir' => $validated['kasir'] ?: $nota->kasir,
                'metode_bayar' => $validated['metode_bayar'] ?? null,
                'kategori_id' => $items[0]['kategori_id'],
                'tanggal' => $validated['tanggal'],
                'nominal' => $total,
                'keterangan' => $this->ringkasKeterangan($items),
                'bukti_file' => $buktiFile,
            ]);

            $nota->items()->delete();
            foreach ($items as $item) {
                $nota->items()->create($item);
            }

            $nota->unsetRelation('items');
            $sesudah = NotaLog::snapshot($nota);

            if ($sebelum != $sesudah) {
                NotaLog::catat($nota, 'ubah', $sebelum, $sesudah);
            }
        });
    }

    // Halaman cetak nota (format formal A4 & struk), karyawan pemilik atau admin.
    // Bisa pilih template kop mana yang dipakai lewat ?template=ID, default ke template default.
    public function cetak(Nota $nota, Request $request)
    {
        $this->authorizeAkses($nota);

        $nota->load(['pengaju', 'kategori', 'items.kategori']);

        $pengaturan = $request->filled('template')
            ? Pengaturan::find($request->template) ?? Pengaturan::aktif()
            : Pengaturan::aktif();

        $daftarTemplate = Pengaturan::orderBy('nama')->get();

        $terbilang = Terbilang::rupiah((int) $nota->nominal);

        return view('nota.cetak', compact('nota', 'terbilang', 'pengaturan', 'daftarTemplate'));
    }

    // Daftar nota: karyawan lihat punya sendiri, admin lihat semua + filter/search
    public function riwayat(Request $request)
    {
        // Admin bisa melihat nota yang sudah dihapus lewat ?terhapus=1 (untuk audit atau dipulihkan).
        $terhapus = Auth::user()->isAdmin() && $request->boolean('terhapus');

        $query = $terhapus
            ? Nota::onlyTrashed()->with(['pengaju', 'kategori', 'penghapus'])
            : Nota::with(['pengaju', 'kategori']);

        if (!Auth::user()->isAdmin()) {
            $query->where('pengaju_id', Auth::id());
        }

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('no_nota', 'like', "%{$cari}%")
                  ->orWhere('kasir', 'like', "%{$cari}%")
                  ->orWhereHas('pengaju', fn ($p) => $p->where('name', 'like', "%{$cari}%"));
            });
        }

        if ($request->filled('kategori_id')) {
            // Cocokkan ke kategori tiap item (bukan cuma kategori nota = item pertama), supaya nota
            // multi-kategori ikut muncul. Nota lama tanpa item tetap tercakup lewat kategori notanya.
            $kategoriId = $request->kategori_id;
            $query->where(function ($q) use ($kategoriId) {
                $q->where('kategori_id', $kategoriId)
                  ->orWhereHas('items', fn ($i) => $i->where('kategori_id', $kategoriId));
            });
        }

        if ($request->filled('bulan')) {
            try {
                $bulan = \Carbon\Carbon::createFromFormat('Y-m', $request->bulan);
                $query->whereYear('tanggal', $bulan->year)
                      ->whereMonth('tanggal', $bulan->month);
            } catch (\Exception $e) {
                // format bulan gak valid, abaikan filter ini
            }
        }

        $riwayat = ($terhapus ? $query->latest('deleted_at') : $query->latest())->paginate(15)->withQueryString();
        $kategori = Kategori::orderBy('nama')->get();

        return view('nota.riwayat', compact('riwayat', 'kategori', 'terhapus'));
    }

    // Hapus nota (admin only, dijaga juga lewat middleware can:admin di routes).
    // Bukan penghapusan permanen: nota ditandai terhapus (soft delete), alasan wajib, dan tercatat di jejak audit.
    public function destroy(Request $request, Nota $nota)
    {
        $this->pastikanPeriodeTerbuka($nota, 'dihapus');

        $data = $request->validate(
            ['alasan' => ['required', 'string', 'min:5', 'max:255']],
            ['alasan.required' => 'Alasan menghapus nota wajib diisi.', 'alasan.min' => 'Alasan terlalu singkat (minimal 5 karakter).']
        );

        DB::transaction(function () use ($nota, $data) {
            NotaLog::catat($nota, 'hapus', NotaLog::snapshot($nota), null, $data['alasan']);

            $nota->forceFill(['dihapus_oleh' => Auth::id(), 'alasan_hapus' => $data['alasan']])->save();
            $nota->delete();
        });

        return redirect()->route('nota.riwayat')
            ->with('success', "Nota {$nota->no_nota} dihapus. Nota masih dapat dipulihkan melalui Daftar Nota → Nota terhapus.");
    }

    // Pulihkan nota yang terhapus (admin only). Nota di periode terkunci tidak bisa dipulihkan.
    public function pulihkan(int $id)
    {
        $nota = Nota::onlyTrashed()->findOrFail($id);

        $this->pastikanPeriodeTerbuka($nota, 'dipulihkan');

        DB::transaction(function () use ($nota) {
            $nota->restore();
            $nota->forceFill(['dihapus_oleh' => null, 'alasan_hapus' => null])->save();

            NotaLog::catat($nota, 'pulihkan', null, NotaLog::snapshot($nota));
        });

        return redirect()->route('nota.show', $nota)->with('success', "Nota {$nota->no_nota} dipulihkan.");
    }

    // Nomor nota otomatis, format: PN-YYYYMMDD-XXX
    // Urutan diambil dari nota terakhir hari ini (bukan count) dan ikut menghitung nota yang sudah dihapus (soft delete),
    // supaya nomor nota yang terhapus tidak dipakai lagi.
    // Dipanggil di dalam transaksi; kalau tetap bentrok, unique constraint + retry di store() yang menjaga.
    private function generateNoNota(): string
    {
        $prefix = 'PN-'.now()->format('Ymd').'-';

        $terakhir = Nota::withTrashed()->where('no_nota', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('no_nota');

        $urutan = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return sprintf('%s%03d', $prefix, $urutan);
    }

    // Ringkas keterangan tiap item jadi satu baris (dipakai untuk tampilan singkat: riwayat, dashboard)
    private function ringkasKeterangan(array $items): ?string
    {
        $pertama = $items[0]['keterangan'] ?? null;
        $sisa = count($items) - 1;

        if (!$pertama) {
            $kategori = Kategori::find($items[0]['kategori_id']);
            $pertama = $kategori->nama ?? 'Item';
        }

        return $sisa > 0 ? "{$pertama} (+{$sisa} item lainnya)" : $pertama;
    }

    // Buang foto bukti yang terlanjur diunggah kalau notanya gagal disimpan, biar tidak menumpuk di storage.
    private function hapusBuktiYatim(?string $buktiFile): void
    {
        if ($buktiFile) {
            Storage::disk(Nota::DISK_BUKTI)->delete($buktiFile);
        }
    }

    // Pastikan karyawan cuma bisa akses nota miliknya sendiri, admin bebas
    private function authorizeAkses(Nota $nota): void
    {
        if (!Auth::user()->isAdmin() && $nota->pengaju_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke nota ini.');
        }
    }

    // Edit dibatasi: admin bebas kapan saja (selama periode belum dikunci), karyawan cuma nota miliknya sendiri
    // dan cuma sampai batas hari di config pettynota.edit_karyawan_hari (default 7 hari).
    private function authorizeEdit(Nota $nota): void
    {
        // Periode terkunci: tidak bisa diubah siapa pun, termasuk admin.
        $this->pastikanPeriodeTerbuka($nota, 'diubah');

        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($nota->pengaju_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke nota ini.');
        }

        if (! $nota->bisaDiedit($user)) {
            $hari = (int) config('pettynota.edit_karyawan_hari', 7);
            abort(403, "Nota hanya bisa diedit sampai {$hari} hari setelah dibuat. Hubungi admin jika nota masih perlu dikoreksi.");
        }
    }

    // Tolak aksi (ubah/hapus/pulihkan) kalau tanggal nota jatuh di periode yang sudah dikunci.
    private function pastikanPeriodeTerbuka(Nota $nota, string $aksi): void
    {
        if (! PeriodeKunci::terkunci($nota->tanggal)) {
            return;
        }

        $label = PeriodeKunci::labelDariTanggal($nota->tanggal);

        abort(403, Auth::user()?->isAdmin()
            ? "Nota ini termasuk periode {$label} yang sudah ditutup accounting sehingga tidak dapat {$aksi}. Buka kunci periode terlebih dahulu."
            : "Nota ini sudah diarsipkan accounting (periode {$label}) sehingga tidak dapat {$aksi}. Hubungi admin jika ada yang perlu dikoreksi.");
    }
}
