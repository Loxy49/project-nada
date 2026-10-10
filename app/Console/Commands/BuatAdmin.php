<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// Buat akun lewat terminal: dipakai untuk admin pertama atau kalau semua admin terkunci.
// Pakai tanpa opsi supaya password diketik lewat prompt (tidak tersimpan di riwayat terminal).
class BuatAdmin extends Command
{
    protected $signature = 'nada:buat-admin {--nama=} {--email=} {--password=} {--role=admin}';

    protected $description = 'Buat akun pengguna (default: admin) lewat terminal';

    public function handle(): int
    {
        $data = [
            'nama' => $this->option('nama') ?: $this->ask('Nama lengkap'),
            'email' => strtolower(trim((string) ($this->option('email') ?: $this->ask('Email')))),
            'password' => $this->option('password') ?: $this->secret('Password (minimal 8 karakter)'),
            'role' => $this->option('role'),
        ];

        $validator = Validator::make($data, [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['karyawan', 'admin'])],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $data['password'],
            'aktif' => true,
        ]);

        $this->info("Akun {$data['role']} untuk {$data['email']} berhasil dibuat.");

        return self::SUCCESS;
    }
}
