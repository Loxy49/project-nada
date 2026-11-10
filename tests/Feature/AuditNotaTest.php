<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Nota;
use App\Models\NotaItem;
use App\Models\NotaLog;
use App\Models\PeriodeKunci;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditNotaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $karyawan;
    private Kategori $parkir;
    private Kategori $atk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Bu Accounting']);
        $this->karyawan = User::factory()->create(['role' => 'karyawan', 'name' => 'Rina']);
        $this->parkir = Kategori::create(['nama' => 'Parkir']);
        $this->atk = Kategori::create(['nama' => 'Alat tulis kantor']);
    }

    private function payload(array $items = null, string $tanggal = null): array
    {
        return [
            'tanggal' => $tanggal ?? now()->toDateString(),
            'kasir' => 'Nada',
            'items' => $items ?? [['kategori_id' => $this->parkir->id, 'keterangan' => 'Parkir', 'nominal' => 5000, 'qty' => 1]],
        ];
    }

    private function buatNota(User $oleh, array $items = null): Nota
    {
        $this->actingAs($oleh)->post(route('nota.store'), $this->payload($items))->assertSessionHasNoErrors();

        return Nota::latest('id')->firstOrFail();
    }

    private function umurkan(Nota $nota, int $hari): void
    {
        $nota->forceFill(['created_at' => now()->subDays($hari)])->save();
    }

    private function kunciBulan(Nota $nota): void
    {
        PeriodeKunci::create([
            'tahun' => $nota->tanggal->year, 'bulan' => $nota->tanggal->month,
            'dikunci_oleh' => $this->admin->id, 'dikunci_pada' => now(),
        ]);
    }

    // ---------- soft delete ----------

    public function test_hapus_nota_adalah_soft_delete_dengan_alasan_dan_item_tetap_ada(): void
    {
        $nota = $this->buatNota($this->admin);

        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Salah input nominal'])
            ->assertRedirect(route('nota.riwayat'));

        $this->assertSoftDeleted('notas', ['id' => $nota->id]);
        $this->assertSame(1, NotaItem::where('nota_id', $nota->id)->count(), 'rincian item tidak boleh ikut hilang');

        $terhapus = Nota::withTrashed()->find($nota->id);
        $this->assertSame($this->admin->id, $terhapus->dihapus_oleh);
        $this->assertSame('Salah input nominal', $terhapus->alasan_hapus);
    }

    public function test_alasan_hapus_wajib_dan_nota_tetap_ada_kalau_kosong(): void
    {
        $nota = $this->buatNota($this->admin);

        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'tes'])->assertSessionHasErrors('alasan');
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota))->assertSessionHasErrors('alasan');

        $this->assertNotSoftDeleted('notas', ['id' => $nota->id]);
    }

    public function test_karyawan_tidak_bisa_menghapus_atau_memulihkan(): void
    {
        $nota = $this->buatNota($this->karyawan);

        $this->actingAs($this->karyawan)->delete(route('nota.destroy', $nota), ['alasan' => 'iseng saja'])->assertForbidden();
        $this->assertNotSoftDeleted('notas', ['id' => $nota->id]);

        $nota->delete();
        $this->actingAs($this->karyawan)->post(route('nota.pulihkan', $nota->id))->assertForbidden();
        $this->assertSoftDeleted('notas', ['id' => $nota->id]);
    }

    public function test_nota_terhapus_tidak_ikut_total_dashboard_dan_rekap(): void
    {
        $nota = $this->buatNota($this->admin);

        $this->assertEquals(5000, NotaItem::rekapPerKategori(now())->sum('total'));

        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Salah input nominal']);

        $this->assertEquals(0, NotaItem::rekapPerKategori(now())->sum('total'));
        $ringkasan = $this->actingAs($this->admin)->get(route('dashboard'))->viewData('ringkasan');
        $this->assertEquals(0, $ringkasan['total_bulan_ini']);
        $this->assertEquals(0, $ringkasan['nota_bulan_ini']);
    }

    public function test_nomor_nota_yang_terhapus_tidak_dipakai_lagi(): void
    {
        $this->buatNota($this->admin);
        $kedua = $this->buatNota($this->admin);

        // Nota terakhir dihapus: kalau nomor dihitung tanpa nota terhapus, nomor 002 dipakai lagi dan bentrok.
        $this->actingAs($this->admin)->delete(route('nota.destroy', $kedua), ['alasan' => 'Salah input nominal']);

        $this->buatNota($this->admin);

        $prefix = 'PN-'.now()->format('Ymd').'-';
        $this->assertSame([$prefix.'001', $prefix.'002', $prefix.'003'], Nota::withTrashed()->orderBy('id')->pluck('no_nota')->all());
    }

    public function test_pulihkan_mengembalikan_nota_dan_tercatat(): void
    {
        $nota = $this->buatNota($this->admin);
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Salah input nominal']);

        $this->actingAs($this->admin)->post(route('nota.pulihkan', $nota->id))->assertRedirect(route('nota.show', $nota));

        $nota->refresh();
        $this->assertNull($nota->deleted_at);
        $this->assertNull($nota->dihapus_oleh);
        $this->assertNull($nota->alasan_hapus);
        $this->assertEquals(5000, NotaItem::rekapPerKategori(now())->sum('total'));
        $this->assertSame(['buat', 'hapus', 'pulihkan'], NotaLog::orderBy('id')->pluck('aksi')->all());
    }

    public function test_nota_di_periode_terkunci_tidak_bisa_dipulihkan(): void
    {
        $nota = $this->buatNota($this->admin);
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Salah input nominal']);
        $this->kunciBulan($nota);

        $this->actingAs($this->admin)->post(route('nota.pulihkan', $nota->id))->assertForbidden();
        $this->assertSoftDeleted('notas', ['id' => $nota->id]);
    }

    public function test_admin_bisa_melihat_daftar_dan_detail_nota_terhapus_karyawan_tidak(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Nota ganda dari Rina']);

        $this->actingAs($this->admin)->get(route('nota.riwayat', ['terhapus' => 1]))
            ->assertOk()->assertSee($nota->no_nota)->assertSee('Nota ganda dari Rina')->assertSee('Pulihkan');
        $this->actingAs($this->admin)->get(route('nota.riwayat'))->assertOk()->assertDontSee($nota->no_nota);
        $this->actingAs($this->admin)->get(route('nota.show', $nota))->assertOk()->assertSee('Nota ganda dari Rina');

        // Karyawan: tidak ada di riwayat (parameter terhapus diabaikan) dan detailnya 404
        $this->actingAs($this->karyawan)->get(route('nota.riwayat', ['terhapus' => 1]))->assertOk()->assertDontSee($nota->no_nota);
        $this->actingAs($this->karyawan)->get(route('nota.show', $nota))->assertNotFound();
    }

    public function test_kategori_yang_dipakai_nota_terhapus_atau_item_kedua_tidak_bisa_dihapus(): void
    {
        $nota = $this->buatNota($this->admin, [
            ['kategori_id' => $this->parkir->id, 'keterangan' => 'Parkir', 'nominal' => 5000, 'qty' => 1],
            ['kategori_id' => $this->atk->id, 'keterangan' => 'Pulpen', 'nominal' => 3000, 'qty' => 1],
        ]);

        // ATK hanya dipakai item kedua (kategori nota = Parkir): dulu lolos cek lalu error 500 karena foreign key.
        $this->actingAs($this->admin)->delete(route('kategori.destroy', $this->atk))->assertSessionHas('error');
        $this->assertNotNull(Kategori::find($this->atk->id));

        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Salah input nominal']);
        $this->actingAs($this->admin)->delete(route('kategori.destroy', $this->parkir))->assertSessionHas('error');
        $this->assertNotNull(Kategori::find($this->parkir->id));
    }

    // ---------- jejak audit ----------

    public function test_pembuatan_nota_tercatat(): void
    {
        $nota = $this->buatNota($this->karyawan);

        $log = NotaLog::firstOrFail();
        $this->assertSame('buat', $log->aksi);
        $this->assertSame($nota->no_nota, $log->no_nota);
        $this->assertSame($this->karyawan->id, $log->user_id);
        $this->assertEquals(5000, $log->sesudah['total']);
        $this->assertNull($log->sebelum);
    }

    public function test_perubahan_nota_tercatat_dengan_sebelum_dan_sesudah(): void
    {
        $nota = $this->buatNota($this->admin);

        $this->actingAs($this->admin)->put(route('nota.update', $nota), $this->payload([
            ['kategori_id' => $this->parkir->id, 'keterangan' => 'Parkir', 'nominal' => 7000, 'qty' => 2],
        ]))->assertSessionHasNoErrors();

        $log = NotaLog::where('aksi', 'ubah')->firstOrFail();
        $this->assertEquals(5000, $log->sebelum['total']);
        $this->assertEquals(14000, $log->sesudah['total']);
        $this->assertContains('Total: Rp5.000 → Rp14.000', $log->perubahan());
        $this->assertStringContainsString('Rincian item', implode(' | ', $log->perubahan()));

        $this->actingAs($this->admin)->get(route('nota.show', $nota))->assertOk()->assertSee('Diubah')->assertSee('Rp5.000 → Rp14.000');
    }

    public function test_simpan_tanpa_perubahan_tidak_menambah_log(): void
    {
        $nota = $this->buatNota($this->admin);

        $this->actingAs($this->admin)->put(route('nota.update', $nota), $this->payload())->assertSessionHasNoErrors();

        $this->assertSame(['buat'], NotaLog::pluck('aksi')->all());
    }

    public function test_halaman_log_nota_hanya_untuk_admin(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Nota ganda dari Rina']);

        $this->actingAs($this->admin)->get(route('lognota.index'))
            ->assertOk()->assertSee($nota->no_nota)->assertSee('Dihapus')->assertSee('Nota ganda dari Rina')->assertSee('Rina');
        $this->actingAs($this->admin)->get(route('lognota.index', ['aksi' => 'hapus']))->assertOk()->assertSee('Dihapus');

        $this->actingAs($this->karyawan)->get(route('lognota.index'))->assertForbidden();
    }

    public function test_log_aktivitas_menyembunyikan_aksi_dibuat_secara_default(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Nota ganda dari Rina']);

        // Yang dicek adalah sel "Aksi" di tabel (<td>...</td>), bukan teks bebas: kata "Dibuat" juga
        // muncul di opsi dropdown filter ("Dibuat saja"), jadi assertSee/DontSee biasa tidak bisa dipakai.
        // Default: hanya ubah/hapus/pulihkan, jadi catatan "Dibuat" tidak muncul.
        $this->actingAs($this->admin)->get(route('lognota.index'))
            ->assertOk()->assertSee('<td>Dihapus</td>', false)->assertDontSee('<td>Dibuat</td>', false);

        // "Semua aksi" dan "Dibuat saja" menampilkannya.
        $this->actingAs($this->admin)->get(route('lognota.index', ['aksi' => 'semua']))
            ->assertOk()->assertSee('<td>Dibuat</td>', false)->assertSee('<td>Dihapus</td>', false);
        $this->actingAs($this->admin)->get(route('lognota.index', ['aksi' => 'buat']))
            ->assertOk()->assertSee('<td>Dibuat</td>', false)->assertDontSee('Nota ganda dari Rina');
    }

    public function test_log_aktivitas_bisa_difilter_per_pengguna_dan_tanggal_serta_menampilkan_total(): void
    {
        $nota = $this->buatNota($this->karyawan, [['kategori_id' => $this->parkir->id, 'keterangan' => 'Parkir', 'nominal' => 12500, 'qty' => 1]]);
        $this->actingAs($this->admin)->delete(route('nota.destroy', $nota), ['alasan' => 'Salah input nominal']);

        $this->actingAs($this->admin)->get(route('lognota.index'))
            ->assertOk()->assertSee('Rp12.500');

        $this->actingAs($this->admin)->get(route('lognota.index', ['user_id' => $this->admin->id]))
            ->assertOk()->assertSee($nota->no_nota);
        $this->actingAs($this->admin)->get(route('lognota.index', ['user_id' => $this->karyawan->id]))
            ->assertOk()->assertDontSee($nota->no_nota);

        $this->actingAs($this->admin)->get(route('lognota.index', ['dari' => now()->subDay()->toDateString(), 'sampai' => now()->toDateString()]))
            ->assertOk()->assertSee($nota->no_nota);
        $this->actingAs($this->admin)->get(route('lognota.index', ['dari' => now()->addDay()->toDateString()]))
            ->assertOk()->assertDontSee($nota->no_nota);
    }

    // ---------- batas edit karyawan ----------

    public function test_karyawan_bisa_mengedit_nota_sendiri_sampai_batas_hari(): void
    {
        $nota = $this->buatNota($this->karyawan);

        $this->umurkan($nota, 3);
        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))->assertOk();
        $this->actingAs($this->karyawan)->put(route('nota.update', $nota), $this->payload([
            ['kategori_id' => $this->parkir->id, 'keterangan' => 'Parkir', 'nominal' => 6000, 'qty' => 1],
        ]))->assertSessionHasNoErrors();
        $this->assertSame($this->karyawan->id, NotaLog::where('aksi', 'ubah')->firstOrFail()->user_id);

        $this->umurkan($nota, 10);
        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))->assertForbidden();
        $this->actingAs($this->karyawan)->put(route('nota.update', $nota), $this->payload())->assertForbidden();
    }

    public function test_batas_hari_edit_bisa_diatur_lewat_config(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->umurkan($nota, 3);

        config(['pettynota.edit_karyawan_hari' => 1]);
        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))->assertForbidden();

        config(['pettynota.edit_karyawan_hari' => 5]);
        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))->assertOk();
    }

    public function test_karyawan_tidak_bisa_mengedit_nota_orang_lain(): void
    {
        $nota = $this->buatNota($this->admin);

        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))->assertForbidden();
    }

    public function test_karyawan_tidak_bisa_mengedit_nota_di_periode_terkunci_meski_masih_dalam_batas_hari(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->kunciBulan($nota);

        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))->assertForbidden();
        $this->actingAs($this->karyawan)->put(route('nota.update', $nota), $this->payload())->assertForbidden();
    }

    // ---------- teks untuk karyawan ----------

    public function test_karyawan_melihat_sudah_diarsipkan_admin_melihat_periode_ditutup(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->kunciBulan($nota);

        $this->actingAs($this->karyawan)->get(route('nota.show', $nota))->assertOk()
            ->assertSee('Sudah diarsipkan')->assertDontSee('Periode ditutup');
        $this->actingAs($this->karyawan)->get(route('nota.riwayat'))->assertOk()
            ->assertSee('Diarsipkan')->assertDontSee('Ditutup');

        $this->actingAs($this->admin)->get(route('nota.show', $nota))->assertOk()->assertSee('Periode ditutup');
        $this->actingAs($this->admin)->get(route('nota.riwayat'))->assertOk()->assertSee('Ditutup');
    }

    public function test_pesan_penolakan_untuk_karyawan_memakai_bahasa_arsip(): void
    {
        $nota = $this->buatNota($this->karyawan);
        $this->kunciBulan($nota);

        $this->actingAs($this->karyawan)->get(route('nota.edit', $nota))
            ->assertForbidden()->assertSee('sudah diarsipkan accounting', false);

        $this->actingAs($this->karyawan)->post(route('nota.store'), $this->payload())->assertSessionHasErrors('tanggal');
        $this->assertStringContainsString('diarsipkan', session('errors')->first('tanggal'));
    }
}
