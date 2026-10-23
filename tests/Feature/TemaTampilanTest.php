<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Mode terang/gelap: tombolnya ada di topbar di samping avatar profil, dan tema dipasang sebelum halaman digambar.
class TemaTampilanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tombol_ganti_tema_ada_di_samping_profil_untuk_semua_peran(): void
    {
        foreach (['karyawan', 'admin'] as $role) {
            $html = $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('dashboard'))->assertOk()
                ->assertSee('id="theme-toggle"', false)
                ->assertSee('onclick="gantiTema()"', false)
                ->getContent();

            // Tombol tepat sebelum dropdown profil (avatar).
            $this->assertLessThan(strpos($html, 'id="profile-menu"'), strpos($html, 'id="theme-toggle"'));
        }
    }

    public function test_menu_log_nota_di_sidebar_hanya_untuk_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('dashboard'))
            ->assertOk()->assertSee(route('lognota.index'), false);

        $this->actingAs(User::factory()->create(['role' => 'karyawan']))->get(route('dashboard'))
            ->assertOk()->assertDontSee(route('lognota.index'), false);
    }

    public function test_tema_dipasang_di_head_sebelum_css_supaya_tidak_berkedip(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('dashboard'))->getContent();

        $pasangTema = strpos($html, "localStorage.getItem('nada-tema')");
        $this->assertNotFalse($pasangTema);
        $this->assertLessThan(strpos($html, '<style>'), $pasangTema);
        $this->assertStringContainsString('html[data-theme="dark"]', $html);
    }
}
