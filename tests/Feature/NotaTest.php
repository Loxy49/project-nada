<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Nota;
use App\Models\NotaItem;
use App\Models\User;
use App\Support\Terbilang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Kategori $parkir;
    private Kategori $atk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->parkir = Kategori::create(['nama' => 'Parkir']);
        $this->atk = Kategori::create(['nama' => 'Alat tulis kantor']);
    }

    private function buatNota(array $items, array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('nota.store'), array_merge([
            'tanggal' => now()->format('Y-m-d'),
            'kasir' => 'Nada',
            'items' => $items,
        ], $extra));
    }

    private function itemParkir(array $extra = []): array
    {
        return array_merge(['kategori_id' => $this->parkir->id, 'keterangan' => 'Parkir', 'nominal' => 5000, 'qty' => 1], $extra);
    }

    public function test_nomor_nota_tidak_kembar_setelah_nota_dihapus(): void
    {
        $this->buatNota([$this->itemParkir()]);
        $this->buatNota([$this->itemParkir()]);
        $this->buatNota([$this->itemParkir()]);

        // Hapus nota ke-2 (alasan wajib), lalu buat nota baru: dulu (count + 1) menghasilkan nomor yang sama dengan nota ke-3.
        $kedua = Nota::where('no_nota', 'like', '%-002')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('nota.destroy', $kedua), ['alasan' => 'Salah input nominal'])
            ->assertRedirect(route('nota.riwayat'));

        $this->buatNota([$this->itemParkir()])->assertSessionHasNoErrors();

        $prefix = 'PN-'.now()->format('Ymd').'-';

        // Nota yang masih aktif: 002 hilang dari daftar, nomor baru adalah 004 (bukan 003 yang bentrok)
        $this->assertSame([$prefix.'001', $prefix.'003', $prefix.'004'], Nota::orderBy('id')->pluck('no_nota')->all());

        // Termasuk nota terhapus (soft delete), tidak ada nomor yang kembar
        $semua = Nota::withTrashed()->orderBy('id')->pluck('no_nota')->all();
        $this->assertSame(count($semua), count(array_unique($semua)));
    }

    public function test_nota_dan_item_tersimpan_bersama_dan_total_benar(): void
    {
        $this->buatNota([
            $this->itemParkir(),
            ['kategori_id' => $this->atk->id, 'keterangan' => 'Pulpen', 'nominal' => 3000, 'qty' => 4],
        ]);

        $nota = Nota::with('items')->firstOrFail();

        $this->assertCount(2, $nota->items);
        $this->assertEquals(5000 + 3000 * 4, $nota->nominal);
    }

    public function test_rekap_per_kategori_membagi_nota_multi_kategori_dengan_benar(): void
    {
        $this->buatNota([
            $this->itemParkir(),
            ['kategori_id' => $this->atk->id, 'keterangan' => 'Kertas', 'nominal' => 50000, 'qty' => 2],
        ]);

        $rekap = NotaItem::rekapPerKategori(now())->pluck('total', 'nama');

        // Dulu seluruh Rp105.000 masuk ke kategori item pertama (Parkir).
        $this->assertEquals(5000, $rekap['Parkir']);
        $this->assertEquals(100000, $rekap['Alat tulis kantor']);
    }

    public function test_dashboard_tidak_menjumlahkan_bulan_yang_sama_di_tahun_lain(): void
    {
        $this->buatNota([$this->itemParkir()]);

        // Nota di bulan yang sama tapi tahun lalu
        Nota::create([
            'no_nota' => 'PN-LAMA-001',
            'pengaju_id' => $this->admin->id,
            'kategori_id' => $this->parkir->id,
            'tanggal' => now()->subYear()->format('Y-m-d'),
            'nominal' => 999999,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $ringkasan = $response->viewData('ringkasan');

        $this->assertEquals(5000, $ringkasan['total_bulan_ini']);
        $this->assertEquals(1, $ringkasan['nota_bulan_ini']);
    }

    public function test_cetak_menampilkan_no_pelanggan_dan_sn_per_item(): void
    {
        $this->buatNota([
            $this->itemParkir(['keterangan' => 'Token listrik 50rb', 'no_pelanggan' => '5555123400', 'sn' => 'SN-TOKEN-777']),
        ]);

        $nota = Nota::firstOrFail();

        $this->actingAs($this->admin)->get(route('nota.cetak', $nota))
            ->assertOk()
            ->assertSee('5555123400')
            ->assertSee('SN-TOKEN-777');

        $this->actingAs($this->admin)->get(route('nota.show', $nota))
            ->assertOk()
            ->assertSee('5555123400')
            ->assertSee('SN-TOKEN-777');
    }

    public function test_export_excel_berhasil_diunduh(): void
    {
        $this->buatNota([$this->itemParkir(['no_pelanggan' => '123', 'sn' => 'ABC'])]);

        $this->actingAs($this->admin)
            ->get(route('rekap.export.excel', ['bulan' => now()->format('Y-m')]))
            ->assertOk();
    }

    public function test_nominal_melebihi_kapasitas_kolom_ditolak_validasi(): void
    {
        // Harga satuan sendiri sudah kebesaran
        $this->buatNota([$this->itemParkir(['nominal' => Nota::NOMINAL_MAKS + 1])])
            ->assertSessionHasErrors('items.0.nominal');

        // Tiap item masih wajar, tapi totalnya (harga x qty) melebihi decimal(12,2)
        $this->buatNota([$this->itemParkir(['nominal' => 2000000000, 'qty' => 5]), $this->itemParkir()])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Nota::count());

        // Tepat di batas masih boleh
        $this->buatNota([$this->itemParkir(['nominal' => Nota::NOMINAL_MAKS])])->assertSessionHasNoErrors();
        $this->assertEquals(Nota::NOMINAL_MAKS, Nota::firstOrFail()->nominal);
    }

    public function test_edit_juga_menolak_total_melebihi_batas(): void
    {
        $this->buatNota([$this->itemParkir()]);
        $nota = Nota::firstOrFail();

        $this->actingAs($this->admin)->put(route('nota.update', $nota), [
            'tanggal' => now()->format('Y-m-d'),
            'items' => [$this->itemParkir(['nominal' => 5000000000, 'qty' => 2]), $this->itemParkir()],
        ])->assertSessionHasErrors('items');

        $this->assertEquals(5000, $nota->fresh()->nominal);
    }

    public function test_filter_kategori_riwayat_mencakup_item_selain_item_pertama(): void
    {
        $this->buatNota([$this->itemParkir(), ['kategori_id' => $this->atk->id, 'keterangan' => 'Pulpen', 'nominal' => 3000, 'qty' => 1]]);
        $this->buatNota([$this->itemParkir(['keterangan' => 'Parkir saja'])]);

        $multi = Nota::orderBy('id')->firstOrFail();
        $parkirSaja = Nota::orderByDesc('id')->firstOrFail();

        // Dicek lewat link baris nota, bukan nomornya (nomor nota juga muncul di pesan flash "berhasil dibuat").
        // ATK cuma ada di item ke-2 nota pertama, jadi nota itu tetap harus muncul
        $this->actingAs($this->admin)->get(route('nota.riwayat', ['kategori_id' => $this->atk->id]))
            ->assertOk()->assertSee(route('nota.show', $multi), false)->assertDontSee(route('nota.show', $parkirSaja), false);

        $this->actingAs($this->admin)->get(route('nota.riwayat', ['kategori_id' => $this->parkir->id]))
            ->assertOk()->assertSee(route('nota.show', $multi), false)->assertSee(route('nota.show', $parkirSaja), false);
    }

    public function test_terbilang_rupiah(): void
    {
        $this->assertSame('Dua belas ribu lima ratus rupiah', Terbilang::rupiah(12500));
        $this->assertSame('Seribu seratus sebelas rupiah', Terbilang::rupiah(1111));
        $this->assertSame('Satu juta rupiah', Terbilang::rupiah(1000000));
    }
}