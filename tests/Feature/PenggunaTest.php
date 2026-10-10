<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function dataAkun(array $extra = []): array
    {
        return array_merge([
            'name' => 'Rina',
            'email' => 'rina@contoh.test',
            'role' => 'karyawan',
            'departemen' => 'Marketing',
            'password' => 'sandi-sementara-1',
            'password_confirmation' => 'sandi-sementara-1',
        ], $extra);
    }

    public function test_admin_bisa_membuat_akun_dan_akun_itu_bisa_login(): void
    {
        $this->actingAs($this->admin)->post(route('pengguna.store'), $this->dataAkun())
            ->assertRedirect(route('pengguna.index'));

        $user = User::where('email', 'rina@contoh.test')->firstOrFail();
        $this->assertSame('karyawan', $user->role);
        $this->assertTrue($user->aktif);
        $this->assertNotSame('sandi-sementara-1', $user->password); // tersimpan ter-hash

        auth()->logout();
        $this->post('/login', ['email' => 'rina@contoh.test', 'password' => 'sandi-sementara-1']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_karyawan_tidak_bisa_mengelola_pengguna(): void
    {
        $karyawan = User::factory()->create(['role' => 'karyawan']);

        $this->actingAs($karyawan)->get(route('pengguna.index'))->assertForbidden();
        $this->actingAs($karyawan)->post(route('pengguna.store'), $this->dataAkun())->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'rina@contoh.test']);
    }

    public function test_validasi_email_unik_peran_dan_password(): void
    {
        $this->actingAs($this->admin)->post(route('pengguna.store'), $this->dataAkun(['email' => $this->admin->email]))
            ->assertSessionHasErrors('email');
        $this->actingAs($this->admin)->post(route('pengguna.store'), $this->dataAkun(['role' => 'superadmin']))
            ->assertSessionHasErrors('role');
        $this->actingAs($this->admin)->post(route('pengguna.store'), $this->dataAkun(['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors('password');
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        User::factory()->create(['email' => 'keluar@contoh.test', 'aktif' => false]);

        $this->post('/login', ['email' => 'keluar@contoh.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_akun_yang_dinonaktifkan_saat_sedang_login_langsung_diputus(): void
    {
        $karyawan = User::factory()->create(['role' => 'karyawan']);

        $this->actingAs($karyawan)->get(route('dashboard'))->assertOk();

        $karyawan->update(['aktif' => false]);

        $this->actingAs($karyawan->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_bisa_menonaktifkan_dan_reset_password_karyawan(): void
    {
        $karyawan = User::factory()->create(['role' => 'karyawan', 'email' => 'budi@contoh.test']);

        $this->actingAs($this->admin)->put(route('pengguna.update', $karyawan), [
            'name' => $karyawan->name,
            'email' => $karyawan->email,
            'role' => 'karyawan',
            'aktif' => '0',
            'password' => 'password-baru-9',
            'password_confirmation' => 'password-baru-9',
        ])->assertRedirect(route('pengguna.index'));

        $karyawan->refresh();
        $this->assertFalse($karyawan->aktif);
        $this->assertTrue(\Hash::check('password-baru-9', $karyawan->password));
    }

    public function test_edit_tanpa_isi_password_tidak_mengubah_password(): void
    {
        $karyawan = User::factory()->create(['role' => 'karyawan']);
        $lama = $karyawan->password;

        $this->actingAs($this->admin)->put(route('pengguna.update', $karyawan), [
            'name' => 'Nama Baru', 'email' => $karyawan->email, 'role' => 'karyawan', 'aktif' => '1',
        ])->assertRedirect(route('pengguna.index'));

        $this->assertSame('Nama Baru', $karyawan->fresh()->name);
        $this->assertSame($lama, $karyawan->fresh()->password);
    }

    public function test_admin_tidak_bisa_menonaktifkan_atau_menurunkan_dirinya_sendiri(): void
    {
        $this->actingAs($this->admin)->put(route('pengguna.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'admin', 'aktif' => '0',
        ])->assertSessionHasErrors('role');

        $this->actingAs($this->admin)->put(route('pengguna.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'karyawan', 'aktif' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertTrue($this->admin->fresh()->aktif);
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_halaman_pengguna_tampil(): void
    {
        User::factory()->create(['name' => 'Sudah Keluar', 'aktif' => false]);

        $this->actingAs($this->admin)->get(route('pengguna.index'))
            ->assertOk()->assertSee('Sudah Keluar')->assertSee('Nonaktif');
        $this->actingAs($this->admin)->get(route('pengguna.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('pengguna.edit', $this->admin))->assertOk();
    }
}
