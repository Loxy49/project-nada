<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeamananTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_tidak_membuat_akun_contoh(): void
    {
        $this->seed();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_perintah_buat_admin_membuat_akun_admin(): void
    {
        $this->artisan('nada:buat-admin', [
            '--nama' => 'Bu Accounting', '--email' => 'ACC@Contoh.test', '--password' => 'rahasia-banget-1',
        ])->assertSuccessful();

        $user = User::where('email', 'acc@contoh.test')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->aktif);
    }

    public function test_perintah_buat_admin_menolak_password_lemah_dan_email_ganda(): void
    {
        User::factory()->create(['email' => 'ada@contoh.test']);

        $this->artisan('nada:buat-admin', ['--nama' => 'X', '--email' => 'baru@contoh.test', '--password' => 'pendek'])->assertFailed();
        $this->artisan('nada:buat-admin', ['--nama' => 'X', '--email' => 'ada@contoh.test', '--password' => 'rahasia-banget-1'])->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test']);
    }

    public function test_cek_keamanan_gagal_untuk_konfigurasi_berbahaya(): void
    {
        config(['app.debug' => true, 'app.url' => 'https://abc.ngrok-free.dev']);
        User::factory()->create(['email' => 'test@example.com', 'role' => 'admin']);

        $this->artisan('nada:cek-keamanan')
            ->expectsOutputToContain('APP_DEBUG mati')
            ->expectsOutputToContain('Tidak ada akun contoh')
            ->assertFailed();
    }

    public function test_cek_keamanan_lolos_untuk_konfigurasi_aman(): void
    {
        config([
            'app.debug' => false,
            'app.env' => 'production',
            'app.url' => 'https://nota.perusahaan.test',
            'session.secure' => true,
        ]);
        $this->app['env'] = 'production';
        User::factory()->create(['role' => 'admin', 'email' => 'acc@perusahaan.test']);

        $this->artisan('nada:cek-keamanan')->assertSuccessful();
    }
}
