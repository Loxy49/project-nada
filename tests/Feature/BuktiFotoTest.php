<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Memakai create() (bukan image()) supaya tes tidak butuh ekstensi GD.
class BuktiFotoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Nota::DISK_BUKTI);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->kategori = Kategori::create(['nama' => 'Parkir']);
    }

    private function kirim(UploadedFile $bukti)
    {
        return $this->actingAs($this->admin)->post(route('nota.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'kasir' => 'Nada',
            'bukti_file' => $bukti,
            'items' => [['kategori_id' => $this->kategori->id, 'keterangan' => 'Parkir', 'nominal' => 5000, 'qty' => 1]],
        ]);
    }

    public function test_foto_di_atas_2mb_ditolak_dengan_pesan_indonesia(): void
    {
        $this->kirim(UploadedFile::fake()->create('besar.jpg', 3000, 'image/jpeg'))
            ->assertSessionHasErrors('bukti_file');

        $this->assertStringContainsString('maksimal 2 MB', session('errors')->first('bukti_file'));
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_file_yang_bukan_gambar_ditolak_dengan_pesan_indonesia(): void
    {
        $this->kirim(UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertSessionHasErrors('bukti_file');

        $this->assertStringContainsString('harus berupa gambar', session('errors')->first('bukti_file'));
    }

    public function test_foto_di_bawah_batas_tersimpan(): void
    {
        $this->kirim(UploadedFile::fake()->create('struk.jpg', 1500, 'image/jpeg'))->assertSessionHasNoErrors();

        $nota = Nota::firstOrFail();
        $this->assertNotNull($nota->bukti_file);
        Storage::disk(Nota::DISK_BUKTI)->assertExists($nota->bukti_file);
        // Disk private: tidak boleh ada salinan di disk public yang bisa dibuka lewat /storage.
        Storage::disk('public')->assertMissing($nota->bukti_file);
    }

    public function test_ganti_foto_menghapus_foto_lama_dan_tanpa_foto_baru_tetap_dipakai(): void
    {
        $this->kirim(UploadedFile::fake()->create('lama.jpg', 100, 'image/jpeg'));
        $nota = Nota::firstOrFail();
        $lama = $nota->bukti_file;

        $ubah = fn (array $tambahan = []) => $this->actingAs($this->admin)->put(route('nota.update', $nota), [
            'tanggal' => now()->format('Y-m-d'),
            'kasir' => 'Nada',
            'items' => [['kategori_id' => $this->kategori->id, 'keterangan' => 'Parkir', 'nominal' => 7000, 'qty' => 1]],
        ] + $tambahan)->assertSessionHasNoErrors();

        // Edit tanpa unggah foto baru: foto lama tetap ada dan tetap dipakai.
        $ubah();
        $this->assertSame($lama, $nota->fresh()->bukti_file);
        Storage::disk(Nota::DISK_BUKTI)->assertExists($lama);

        // Ganti foto: foto baru tersimpan, foto lama dihapus.
        $ubah(['bukti_file' => UploadedFile::fake()->create('baru.jpg', 100, 'image/jpeg')]);
        $baru = $nota->fresh()->bukti_file;
        $this->assertNotSame($lama, $baru);
        Storage::disk(Nota::DISK_BUKTI)->assertExists($baru);
        Storage::disk(Nota::DISK_BUKTI)->assertMissing($lama);
    }

    public function test_migrasi_memindahkan_foto_lama_dari_disk_public_ke_private(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bukti-nota/lama.jpg', 'isi-foto');

        $migrasi = require database_path('migrations/2024_01_01_000026_pindahkan_bukti_nota_ke_disk_private.php');
        $migrasi->up();

        Storage::disk('public')->assertMissing('bukti-nota/lama.jpg');
        $this->assertSame('isi-foto', Storage::disk(Nota::DISK_BUKTI)->get('bukti-nota/lama.jpg'));
    }

    public function test_form_buat_dan_edit_memuat_skrip_kompres_foto(): void
    {
        $this->actingAs($this->admin)->get(route('nota.create'))->assertOk()->assertSee('MAKS_SISI', false);

        $this->kirim(UploadedFile::fake()->create('struk.jpg', 100, 'image/jpeg'));
        $nota = Nota::firstOrFail();

        $this->actingAs($this->admin)->get(route('nota.edit', $nota))->assertOk()->assertSee('MAKS_SISI', false);
    }
}