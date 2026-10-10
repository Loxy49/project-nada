<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Nota;
use App\Models\PeriodeKunci;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodeKunciTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Kategori $kategori;
    private Carbon $bulanLalu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->kategori = Kategori::create(['nama' => 'Parkir']);
        $this->bulanLalu = now()->startOfMonth()->subMonth();
    }

    private function notaBulanLalu(): Nota
    {
        $nota = Nota::create([
            'no_nota' => 'PN-LAMA-'.uniqid(),
            'pengaju_id' => $this->admin->id,
            'kategori_id' => $this->kategori->id,
            'tanggal' => $this->bulanLalu->copy()->addDays(9)->toDateString(),
            'nominal' => 10000,
        ]);
        $nota->items()->create(['kategori_id' => $this->kategori->id, 'keterangan' => 'Parkir', 'nominal' => 10000, 'qty' => 1]);

        return $nota;
    }

    private function kunciBulanLalu()
    {
        return $this->actingAs($this->admin)->post(route('periode.kunci'), [
            'bulan' => $this->bulanLalu->format('Y-m'),
            'catatan' => 'Sudah direkap',
        ]);
    }

    private function payloadNota(string $tanggal): array
    {
        return [
            'tanggal' => $tanggal,
            'kasir' => 'Nada',
            'items' => [['kategori_id' => $this->kategori->id, 'keterangan' => 'Parkir', 'nominal' => 5000, 'qty' => 1]],
        ];
    }

    public function test_admin_bisa_mengunci_bulan_lalu_dan_tercatat(): void
    {
        $this->kunciBulanLalu()->assertSessionHas('success');

        $this->assertTrue(PeriodeKunci::terkunci($this->bulanLalu));
        $this->assertDatabaseHas('periode_kuncis', [
            'tahun' => $this->bulanLalu->year,
            'bulan' => $this->bulanLalu->month,
            'dikunci_oleh' => $this->admin->id,
            'catatan' => 'Sudah direkap',
        ]);
    }

    public function test_bulan_berjalan_tidak_bisa_dikunci(): void
    {
        $this->actingAs($this->admin)
            ->post(route('periode.kunci'), ['bulan' => now()->format('Y-m')])
            ->assertSessionHas('error');

        $this->assertFalse(PeriodeKunci::terkunci(now()));
        $this->assertDatabaseCount('periode_kuncis', 0);
    }

    public function test_periode_tidak_bisa_dikunci_dua_kali(): void
    {
        $this->kunciBulanLalu();
        $this->kunciBulanLalu()->assertSessionHas('error');

        $this->assertDatabaseCount('periode_kuncis', 1);
    }

    public function test_nota_di_periode_terkunci_tidak_bisa_diedit_atau_dihapus_bahkan_oleh_admin(): void
    {
        $nota = $this->notaBulanLalu();

        $this->kunciBulanLalu();

        $this->actingAs($this->admin)->get(route('nota.edit', $nota))->assertForbidden();

        $this->actingAs($this->admin)->put(route('nota.update', $nota), $this->payloadNota(now()->toDateString()))
            ->assertForbidden();

        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota))->assertForbidden();
        $this->assertDatabaseHas('notas', ['id' => $nota->id]);
    }

    public function test_nota_baru_ke_periode_terkunci_ditolak_tapi_bulan_berjalan_tetap_bisa(): void
    {
        $this->kunciBulanLalu();

        $this->actingAs($this->admin)
            ->post(route('nota.store'), $this->payloadNota($this->bulanLalu->copy()->addDays(5)->toDateString()))
            ->assertSessionHasErrors('tanggal');
        $this->assertDatabaseCount('notas', 0);

        $this->actingAs($this->admin)
            ->post(route('nota.store'), $this->payloadNota(now()->toDateString()))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notas', 1);
    }

    public function test_memindahkan_nota_terbuka_ke_periode_terkunci_ditolak(): void
    {
        $this->kunciBulanLalu();

        $this->actingAs($this->admin)->post(route('nota.store'), $this->payloadNota(now()->toDateString()));
        $nota = Nota::firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('nota.update', $nota), $this->payloadNota($this->bulanLalu->copy()->addDays(3)->toDateString()))
            ->assertSessionHasErrors('tanggal');
    }

    public function test_buka_kunci_wajib_alasan_dan_setelahnya_nota_bisa_diedit_lagi(): void
    {
        $nota = $this->notaBulanLalu();
        $this->kunciBulanLalu();
        $kunci = PeriodeKunci::firstOrFail();

        $this->actingAs($this->admin)->post(route('periode.buka', $kunci), ['alasan' => ''])
            ->assertSessionHasErrors('alasan');
        $this->assertTrue(PeriodeKunci::terkunci($this->bulanLalu));

        $this->actingAs($this->admin)->post(route('periode.buka', $kunci), ['alasan' => 'Koreksi nominal parkir'])
            ->assertSessionHas('success');

        $kunci->refresh();
        $this->assertNotNull($kunci->dibuka_pada);
        $this->assertSame($this->admin->id, $kunci->dibuka_oleh);
        $this->assertSame('Koreksi nominal parkir', $kunci->alasan_buka);
        $this->assertFalse(PeriodeKunci::terkunci($this->bulanLalu));

        $this->actingAs($this->admin)->get(route('nota.edit', $nota))->assertOk();

        // Riwayat dipertahankan: bulan yang sama bisa dikunci lagi sebagai baris baru.
        $this->kunciBulanLalu()->assertSessionHas('success');
        $this->assertDatabaseCount('periode_kuncis', 2);
    }

    public function test_alasan_buka_kunci_terlalu_pendek_menampilkan_pesan_dan_tetap_terkunci(): void
    {
        $this->kunciBulanLalu();
        $kunci = PeriodeKunci::firstOrFail();

        $this->actingAs($this->admin)->from(route('periode.index'))->followingRedirects()
            ->post(route('periode.buka', $kunci), ['alasan' => 'tes'])
            ->assertSee('minimal 5 karakter');

        $this->assertTrue(PeriodeKunci::terkunci($this->bulanLalu));
    }

    public function test_form_periode_tidak_bergantung_pada_dialog_prompt_browser(): void
    {
        $this->kunciBulanLalu();

        $this->actingAs($this->admin)->get(route('periode.index'))
            ->assertOk()
            ->assertDontSee('prompt(', false)
            ->assertSee('name="alasan"', false);
    }

    public function test_karyawan_tidak_bisa_mengakses_atau_mengubah_kunci_periode(): void
    {
        $karyawan = User::factory()->create(['role' => 'karyawan']);

        $this->actingAs($karyawan)->get(route('periode.index'))->assertForbidden();
        $this->actingAs($karyawan)->post(route('periode.kunci'), ['bulan' => $this->bulanLalu->format('Y-m')])->assertForbidden();
        $this->assertDatabaseCount('periode_kuncis', 0);
    }

    public function test_tampilan_menyembunyikan_edit_hapus_dan_menandai_periode_ditutup(): void
    {
        $nota = $this->notaBulanLalu();
        $this->kunciBulanLalu();

        $this->actingAs($this->admin)->get(route('periode.index'))
            ->assertOk()
            ->assertSee('Terkunci')
            ->assertSee('Sudah direkap');

        $this->actingAs($this->admin)->get(route('nota.show', $nota))
            ->assertOk()
            ->assertSee('Periode ditutup')
            ->assertDontSee(route('nota.edit', $nota))
            ->assertDontSee('Alasan menghapus nota (min. 5 karakter)', false);

        $this->actingAs($this->admin)->get(route('nota.riwayat'))
            ->assertOk()
            ->assertSee('Ditutup')
            ->assertDontSee('#hapus', false);
    }

    public function test_cetak_ulang_tetap_bisa_di_periode_terkunci(): void
    {
        $nota = $this->notaBulanLalu();
        $this->kunciBulanLalu();

        $this->actingAs($this->admin)->get(route('nota.cetak', $nota))->assertOk();
    }

    public function test_label_bulan_berbahasa_indonesia(): void
    {
        $this->assertSame('September 2026', PeriodeKunci::labelDariTanggal('2026-09-15'));
        $this->assertSame('Agustus 2025', PeriodeKunci::labelBulan(2025, 8));
    }
}
