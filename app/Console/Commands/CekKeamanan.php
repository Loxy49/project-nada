<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

// Jalankan di SERVER produksi sebelum dipakai tim: php artisan nada:cek-keamanan
class CekKeamanan extends Command
{
    protected $signature = 'nada:cek-keamanan';

    protected $description = 'Periksa pengaturan keamanan dasar sebelum aplikasi dipakai sungguhan';

    public function handle(): int
    {
        $url = (string) config('app.url');

        $cek = [
            [! config('app.debug'), 'APP_DEBUG mati', 'Set APP_DEBUG=false di .env (kalau menyala, error menampilkan detail kode dan konfigurasi).'],
            [app()->environment('production'), 'APP_ENV=production', 'Set APP_ENV=production di .env server.'],
            [filled(config('app.key')), 'APP_KEY terisi', 'Jalankan: php artisan key:generate'],
            [str_starts_with($url, 'https://') && ! str_contains($url, 'ngrok') && ! str_contains($url, 'localhost'),
                'APP_URL memakai https dan bukan ngrok/localhost', 'Isi APP_URL dengan alamat HTTPS tetap milik server.'],
            [config('session.secure') === true, 'Cookie sesi hanya lewat HTTPS', 'Set SESSION_SECURE_COOKIE=true di .env (setelah situs memakai HTTPS).'],
            [! Route::has('register'), 'Registrasi publik tertutup', 'Hapus rute register di routes/auth.php.'],
            [User::where('role', 'admin')->where('aktif', true)->exists(), 'Ada minimal 1 admin aktif', 'Buat dengan: php artisan nada:buat-admin'],
            [! User::where('email', 'test@example.com')->exists(), 'Tidak ada akun contoh test@example.com', 'Hapus akun itu dari tabel users (password bawaannya "password").'],
        ];

        $masalah = 0;
        foreach ($cek as [$ok, $label, $saran]) {
            if ($ok) {
                $this->line("  <fg=green>✓</> {$label}");
            } else {
                $masalah++;
                $this->line("  <fg=red>✗</> {$label}");
                $this->line("      → {$saran}");
            }
        }

        $this->newLine();
        $this->line('Yang tidak bisa dicek otomatis: password SMTP (MAIL_PASSWORD) sudah diganti kalau pernah dibagikan, dan backup database rutin.');

        if ($masalah > 0) {
            $this->error("{$masalah} hal perlu diperbaiki.");

            return self::FAILURE;
        }

        $this->info('Semua pemeriksaan lolos.');

        return self::SUCCESS;
    }
}
