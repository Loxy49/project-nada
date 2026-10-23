<?php

namespace App\Http\Requests;

use App\Models\Nota;
use App\Models\PeriodeKunci;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// Validasi form nota, dipakai bareng oleh simpan (store) dan ubah (update).
// Hak akses tetap dicek di NotaController (authorizeEdit), di sini cuma bentuk datanya.
class NotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => $this->aturanTanggal(),
            'kasir' => 'nullable|string|max:100',
            'metode_bayar' => 'nullable|string|max:50',
            'bukti_file' => 'nullable|file|image|max:2048',
            'items' => 'required|array|min:1',
            'items.*.kategori_id' => 'required|exists:kategoris,id',
            'items.*.keterangan' => 'nullable|string|max:255',
            'items.*.nominal' => 'required|numeric|min:1|max:'.Nota::NOMINAL_MAKS,
            'items.*.qty' => 'nullable|integer|min:1|max:'.Nota::QTY_MAKS,
            'items.*.no_pelanggan' => 'nullable|string|max:100',
            'items.*.sn' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'bukti_file.image' => 'Bukti harus berupa gambar (JPG atau PNG).',
            'bukti_file.max' => 'Ukuran foto bukti maksimal 2 MB.',
            'bukti_file.uploaded' => 'Foto bukti gagal diunggah, kemungkinan terlalu besar (maksimal 2 MB). Pilih foto yang lebih kecil.',
            'items.*.nominal.max' => 'Harga satuan maksimal Rp'.number_format(Nota::NOMINAL_MAKS, 0, ',', '.').'.',
            'items.*.qty.max' => 'Qty maksimal '.number_format(Nota::QTY_MAKS, 0, ',', '.').'.',
        ];
    }

    // Total nota (harga satuan x qty semua item) juga harus muat di kolom nominal (decimal 12,2),
    // kalau tidak database menolak dan user dapat error 500.
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->total() > Nota::NOMINAL_MAKS) {
                    $validator->errors()->add('items', 'Total nota maksimal Rp'.number_format(Nota::NOMINAL_MAKS, 0, ',', '.').'. Pecah menjadi beberapa nota.');
                }
            },
        ];
    }

    // Item yang sudah tervalidasi, qty diisi 1 kalau kosong.
    public function items(): array
    {
        return array_map(fn ($item) => array_merge($item, ['qty' => $item['qty'] ?? 1]), $this->validated('items'));
    }

    // Total nota = jumlah (harga satuan x qty) tiap item
    public function total(): float
    {
        return array_sum(array_map(fn ($item) => $item['nominal'] * $item['qty'], $this->items()));
    }

    // Aturan tanggal nota: harus tanggal valid dan tidak jatuh di periode yang sudah dikunci accounting.
    // Berlaku untuk semua role (admin juga) dan untuk memindahkan nota ke bulan terkunci.
    private function aturanTanggal(): array
    {
        return [
            'bail', 'required', 'date',
            function (string $attribute, mixed $value, \Closure $fail) {
                if (PeriodeKunci::terkunci($value)) {
                    $label = PeriodeKunci::labelDariTanggal($value);
                    $fail($this->user()?->isAdmin()
                        ? "Periode {$label} sudah ditutup oleh accounting sehingga nota tidak dapat dibuat atau dipindahkan ke bulan tersebut. Buka kunci periode terlebih dahulu jika diperlukan."
                        : "Periode {$label} sudah diarsipkan accounting sehingga nota tidak dapat dibuat atau dipindahkan ke bulan tersebut. Hubungi admin.");
                }
            },
        ];
    }
}
