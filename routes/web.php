<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\LogNotaController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RekapExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ajukan & lihat nota (tanpa alur approval — begitu dibuat, langsung final)
    Route::get('/nota/create', [NotaController::class, 'create'])->name('nota.create');
    Route::post('/nota', [NotaController::class, 'store'])->name('nota.store');
    Route::get('/nota/{nota}', [NotaController::class, 'show'])->name('nota.show')->withTrashed();
    Route::get('/nota/{nota}/edit', [NotaController::class, 'edit'])->name('nota.edit');
    Route::get('/nota/{nota}/bukti', [NotaController::class, 'bukti'])->name('nota.bukti')->withTrashed();
    Route::put('/nota/{nota}', [NotaController::class, 'update'])->name('nota.update');
    Route::get('/nota/{nota}/cetak', [NotaController::class, 'cetak'])->name('nota.cetak');

    // Admin: kelola kategori, template nota, dan hapus nota
    Route::middleware(['can:admin'])->group(function () {
        Route::delete('/nota/{nota}', [NotaController::class, 'destroy'])->name('nota.destroy');
        Route::post('/nota/{id}/pulihkan', [NotaController::class, 'pulihkan'])->name('nota.pulihkan');
        Route::get('/log-nota', [LogNotaController::class, 'index'])->name('lognota.index');
        Route::resource('/kategori', KategoriController::class)->except(['show']);
        Route::resource('/pengguna', UserController::class)->parameters(['pengguna' => 'user'])->except(['show', 'destroy']);
        Route::resource('/pengaturan', PengaturanController::class)->except(['show']);
        Route::post('/pengaturan/{pengaturan}/jadikan-default', [PengaturanController::class, 'jadikanDefault'])->name('pengaturan.default');

        // Kunci periode (tutup buku bulanan) buat tim accounting
        Route::get('/periode', [PeriodeController::class, 'index'])->name('periode.index');
        Route::post('/periode/kunci', [PeriodeController::class, 'kunci'])->name('periode.kunci');
        Route::post('/periode/{periode}/buka', [PeriodeController::class, 'buka'])->name('periode.buka');

        // Ekspor rekap pengeluaran bulanan buat tim accounting
        Route::get('/rekap/export/excel', [RekapExportController::class, 'excel'])->name('rekap.export.excel');
        Route::get('/rekap/export/pdf', [RekapExportController::class, 'pdf'])->name('rekap.export.pdf');
    });

    // Riwayat
    Route::get('/riwayat', [NotaController::class, 'riwayat'])->name('nota.riwayat');

    // Profile bawaan Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
