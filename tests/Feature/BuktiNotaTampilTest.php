<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Foto bukti dilayani lewat aplikasi (route nota.bukti), bukan lewat link publik storage.
// Gambar uji dibuat dari byte PNG kecil supaya tes tidak butuh ekstensi GD.
class BuktiNotaTampilTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFklEQVR4nGO0SXFjYGBgYmBgYGBgAAAK6ADqW1Z7mgAAAABJRU5ErkJggg==';

    private User $admin;
    private User $pemilik;
    private User $orangLain;
    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Nota::DISK_BUKTI);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pemilik = User::factory()->create(['role' => 'karyawan']);
        $this->orangLain = User::factory()->create(['role' => 'karyawan']);
        $this->kategori = Kategori::create(['nama' => 'Parkir']);
    }

    private function notaDenganBukti(bool $adaFile = true): Nota
    {
        $path = 'bukti-nota/tes-'.uniqid().'.png';
        if ($adaFile) {
            Storage::disk(Nota::DISK_BUKTI)->put($path, base64_decode(self::PNG));
        }

        return Nota::create([
            'no_nota' => 'PN-TES-'.uniqid(),
            'pengaju_id' => $this->pemilik->id,
            'kategori_id' => $this->kategori->id,
            'tanggal' => now()->toDateString(),
            'nominal' => 5000,
            'bukti_file' => $path,
        ]);
    }

    public function test_pemilik_dan_admin_bisa_melihat_foto_bukti(): void
    {
        $nota = $this->notaDenganBukti();

        foreach ([$this->pemilik, $this->admin] as $user) {
            $res = $this->actingAs($user)->get(route('nota.bukti', $nota))->assertOk();
            $this->assertSame('image/png', $res->headers->get('Content-Type'));
        }
    }

    public function test_orang_lain_dan_tamu_tidak_bisa_melihat_foto_bukti(): void
    {
        $nota = $this->notaDenganBukti();

        $this->actingAs($this->orangLain)->get(route('nota.bukti', $nota))->assertForbidden();

        auth()->logout();
        $this->get(route('nota.bukti', $nota))->assertRedirect(route('login'));
    }

    public function test_nota_tanpa_foto_atau_file_hilang_memberi_404_bukan_error(): void
    {
        $tanpaFoto = Nota::create([
            'no_nota' => 'PN-TES-KOSONG', 'pengaju_id' => $this->pemilik->id, 'kategori_id' => $this->kategori->id,
            'tanggal' => now()->toDateString(), 'nominal' => 1000,
        ]);
        $fileHilang = $this->notaDenganBukti(false);

        $this->actingAs($this->admin)->get(route('nota.bukti', $tanpaFoto))->assertNotFound();
        $this->actingAs($this->admin)->get(route('nota.bukti', $fileHilang))->assertNotFound();
    }

    public function test_foto_nota_terhapus_hanya_untuk_admin(): void
    {
        $nota = $this->notaDenganBukti();
        $nota->delete();

        $this->actingAs($this->admin)->get(route('nota.bukti', $nota))->assertOk();
        $this->actingAs($this->pemilik)->get(route('nota.bukti', $nota))->assertNotFound();
    }

    public function test_halaman_detail_menampilkan_foto_atau_keterangan_tidak_ada(): void
    {
        $nota = $this->notaDenganBukti();
        $url = route('nota.bukti', $nota);

        $this->actingAs($this->admin)->get(route('nota.show', $nota))
            ->assertOk()->assertSee('Bukti pendukung')->assertSee('<img src="'.$url.'"', false);

        $tanpa = Nota::create([
            'no_nota' => 'PN-TES-KOSONG', 'pengaju_id' => $this->pemilik->id, 'kategori_id' => $this->kategori->id,
            'tanggal' => now()->toDateString(), 'nominal' => 1000,
        ]);
        $this->actingAs($this->admin)->get(route('nota.show', $tanpa))
            ->assertOk()->assertSee('Tidak ada foto bukti');
    }

    public function test_form_edit_memakai_route_aplikasi_bukan_link_storage(): void
    {
        $nota = $this->notaDenganBukti();

        $this->actingAs($this->admin)->get(route('nota.edit', $nota))
            ->assertOk()->assertSee(route('nota.bukti', $nota), false)->assertDontSee('/storage/bukti-nota', false);
    }

    public function test_foto_bukti_tidak_ada_di_halaman_cetak(): void
    {
        $nota = $this->notaDenganBukti();

        // Foto bukti cuma arsip: dilihat dari riwayat atau detail nota, tidak pernah ikut tercetak.
        $this->actingAs($this->admin)->get(route('nota.cetak', $nota))
            ->assertOk()
            ->assertDontSee(route('nota.bukti', $nota), false)
            ->assertDontSee('/storage/bukti-nota', false)
            ->assertDontSee('Lampiran bukti');
    }

    public function test_riwayat_menampilkan_tautan_bukti_hanya_untuk_nota_yang_punya_foto(): void
    {
        $denganFoto = $this->notaDenganBukti();
        $tanpaFoto = Nota::create([
            'no_nota' => 'PN-TES-KOSONG', 'pengaju_id' => $this->pemilik->id, 'kategori_id' => $this->kategori->id,
            'tanggal' => now()->toDateString(), 'nominal' => 1000,
        ]);

        foreach ([$this->pemilik, $this->admin] as $user) {
            $this->actingAs($user)->get(route('nota.riwayat'))
                ->assertOk()
                ->assertSee(route('nota.bukti', $denganFoto), false)
                ->assertDontSee(route('nota.bukti', $tanpaFoto), false);
        }

        // Karyawan lain tidak melihat nota (dan tautan bukti) milik orang lain.
        $this->actingAs($this->orangLain)->get(route('nota.riwayat'))
            ->assertOk()->assertDontSee(route('nota.bukti', $denganFoto), false);
    }

    public function test_daftar_nota_terhapus_menampilkan_tautan_bukti_untuk_admin(): void
    {
        $nota = $this->notaDenganBukti();
        $nota->delete();

        $this->actingAs($this->admin)->get(route('nota.riwayat', ['terhapus' => 1]))
            ->assertOk()->assertSee(route('nota.bukti', $nota), false);
    }
}
