<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Registrasi publik sengaja ditutup. Akun dibuat admin lewat menu Pengguna.
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_registrasi_tidak_ada(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_orang_luar_tidak_bisa_mendaftar_lewat_post(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.com']);
    }

    public function test_halaman_login_tidak_menawarkan_pendaftaran(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Daftar')->assertSee('Hubungi admin');
    }
}
