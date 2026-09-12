@extends('layouts.app')
@section('title', 'Edit Nota')

@section('content')
    <div class="card" style="max-width:640px;">
        <p style="font-size:12.5px; color:#6b7280; margin-top:0;">Mengedit <strong>{{ $nota->no_nota }}</strong>. Perubahan langsung menimpa data nota ini.</p>

        <form method="POST" action="{{ route('nota.update', $nota) }}" enctype="multipart/form-data" id="form-nota">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert alert-error" style="margin-bottom:16px;">
                    <strong>Ada yang perlu diperbaiki:</strong>
                    <ul style="margin:6px 0 0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="field">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', $nota->tanggal->format('Y-m-d')) }}" required>
                @error('tanggal') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label>Kasir</label>
                <input type="text" name="kasir" value="{{ old('kasir', $nota->kasir) }}" placeholder="Nama kasir yang input transaksi ini">
                @error('kasir') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label>Metode bayar</label>
                <select name="metode_bayar">
                    <option value="">-- pilih metode bayar --</option>
                    @foreach(['Tunai', 'Transfer Bank', 'QRIS', 'Kartu Debit/Kredit', 'Lainnya'] as $metode)
                        <option value="{{ $metode }}" @selected(old('metode_bayar', $nota->metode_bayar) === $metode)>{{ $metode }}</option>
                    @endforeach
                </select>
            </div>

            <label style="display:block; margin:16px 0 8px; font-weight:600;">Rincian item</label>
            <div id="daftar-item"></div>

            <button type="button" id="btn-tambah-item" class="btn" style="background:#fff; color:#5a7a62; border:1px solid #84a98c; margin-bottom:16px;">+ Tambah item</button>

            <div style="display:flex; justify-content:space-between; align-items:center; background:#f7f6f2; border-radius:8px; padding:12px 16px; margin-bottom:16px;">
                <span style="font-size:13px; color:#6b7280;">Total nota</span>
                <strong id="total-tampil" style="font-size:18px;">Rp0</strong>
            </div>

            <div class="field">
                <label>Bukti pendukung</label>
                @if($nota->bukti_file)
                    <div style="margin-bottom:6px;"><img src="{{ route('nota.bukti', $nota) }}" alt="Bukti nota" style="max-width:160px; border-radius:8px; border:1px solid #e5e4e2;"></div>
                @endif
                <input type="file" name="bukti_file" accept="image/*">
                <small style="color:#9ca3af;">Kosongkan jika foto bukti yang sudah ada tidak ingin diganti.</small>
            </div>

            <button type="submit" class="btn" style="width:100%;">Simpan perubahan</button>
        </form>
    </div>

    {{-- Template 1 baris item (disalin oleh JS) --}}
    <template id="template-item">
        <div class="baris-item" style="border:1px solid #e5e4e2; border-radius:8px; padding:14px; margin-bottom:10px; position:relative;">
            <button type="button" class="btn-hapus-item" style="position:absolute; top:10px; right:10px; background:none; border:none; color:#9ca3af; cursor:pointer; display:flex; padding:2px;" title="Hapus item" aria-label="Hapus item"><x-ikon nama="tutup" :ukuran="16" /></button>

            <div class="field" style="margin-bottom:10px;">
                <label style="font-size:12.5px;">Kategori</label>
                <select name="items[][kategori_id]" required>
                    <option value="">-- pilih kategori --</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="margin-bottom:10px;">
                <label style="font-size:12.5px;">Keterangan / jenis transaksi</label>
                <input type="text" name="items[][keterangan]" class="in-keterangan" placeholder="Contoh: Token Listrik 50rb">
            </div>

            <div style="display:flex; gap:10px;">
                <div class="field" style="margin-bottom:0; flex:2;">
                    <label style="font-size:12.5px;">Harga satuan (Rp)</label>
                    <input type="number" name="items[][nominal]" class="in-nominal" min="1" step="1" required>
                </div>
                <div class="field" style="margin-bottom:0; flex:1;">
                    <label style="font-size:12.5px;">Qty</label>
                    <input type="number" name="items[][qty]" class="in-qty" min="1" step="1" value="1" required>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:12.5px; color:#6b7280; margin-top:8px;">
                <button type="button" class="btn-toggle-pelanggan" style="background:none; border:none; color:#5a7a62; cursor:pointer; padding:0; font-size:12.5px;">+ Ini pembelian pulsa/token/PDAM?</button>
                <span>Subtotal: <strong class="subtotal-tampil">Rp0</strong></span>
            </div>

            <div class="blok-pelanggan" style="display:none; margin-top:10px; padding-top:10px; border-top:1px dashed #e5e4e2;">
                <div style="display:flex; gap:10px;">
                    <div class="field" style="margin-bottom:0; flex:1;">
                        <label style="font-size:12.5px;">No. Pelanggan / No. Meter</label>
                        <input type="text" name="items[][no_pelanggan]" class="in-no_pelanggan" placeholder="Contoh: ID pelanggan PLN/PDAM">
                    </div>
                    <div class="field" style="margin-bottom:0; flex:1;">
                        <label style="font-size:12.5px;">SN / No. Seri Token</label>
                        <input type="text" name="items[][sn]" class="in-sn" placeholder="Nomor seri token/voucher">
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        const daftarItem = document.getElementById('daftar-item');
        const templateItem = document.getElementById('template-item');
        const totalTampil = document.getElementById('total-tampil');
        let indeksItem = 0;

        function formatRupiah(angka) {
            return 'Rp' + (angka || 0).toLocaleString('id-ID');
        }

        function hitungSubtotal(baris) {
            const nominal = Number(baris.querySelector('.in-nominal').value || 0);
            const qty = Number(baris.querySelector('.in-qty').value || 0);
            const subtotal = nominal * qty;
            baris.querySelector('.subtotal-tampil').textContent = formatRupiah(subtotal);
            return subtotal;
        }

        function hitungTotal() {
            let total = 0;
            daftarItem.querySelectorAll('.baris-item').forEach(baris => {
                total += hitungSubtotal(baris);
            });
            totalTampil.textContent = formatRupiah(total);
        }

        function tambahItem(data = {}) {
            const klon = templateItem.content.cloneNode(true);
            const baris = klon.querySelector('.baris-item');
            const idx = indeksItem++;

            baris.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace('items[]', `items[${idx}]`);
            });

            if (data.kategori_id) baris.querySelector('select').value = data.kategori_id;
            if (data.keterangan) baris.querySelector('.in-keterangan').value = data.keterangan;
            if (data.nominal) baris.querySelector('.in-nominal').value = data.nominal;
            baris.querySelector('.in-qty').value = data.qty || 1;

            const blokPelanggan = baris.querySelector('.blok-pelanggan');
            const btnToggle = baris.querySelector('.btn-toggle-pelanggan');
            if (data.no_pelanggan) baris.querySelector('.in-no_pelanggan').value = data.no_pelanggan;
            if (data.sn) baris.querySelector('.in-sn').value = data.sn;
            if (data.no_pelanggan || data.sn) {
                blokPelanggan.style.display = 'block';
                btnToggle.textContent = '- Sembunyikan info pelanggan';
            }
            btnToggle.addEventListener('click', () => {
                const tampil = blokPelanggan.style.display === 'block';
                blokPelanggan.style.display = tampil ? 'none' : 'block';
                btnToggle.textContent = tampil ? '+ Ini pembelian pulsa/token/PDAM?' : '- Sembunyikan info pelanggan';
            });

            baris.querySelector('.btn-hapus-item').addEventListener('click', () => {
                if (daftarItem.querySelectorAll('.baris-item').length > 1) {
                    baris.remove();
                    hitungTotal();
                }
            });
            baris.querySelector('.in-nominal').addEventListener('input', hitungTotal);
            baris.querySelector('.in-qty').addEventListener('input', hitungTotal);

            daftarItem.appendChild(klon);
        }

        document.getElementById('btn-tambah-item').addEventListener('click', () => tambahItem());

        // Prioritas isi ulang: kalau validasi gagal pakai input lama; kalau tidak, pakai item nota yang ada.
        const itemLama = @json(old('items', []));
        @php
            // PENTING: hitung dulu di variabel PHP biasa, JANGAN taruh langsung di dalam @json(...).
            // @json() Blade memecah argumennya berdasarkan koma, jadi kalau isinya array
            // multi-baris yang ada banyak koma di dalamnya, hasil compile-nya jadi rusak.
            $itemNotaUntukJs = $nota->items->map(fn ($i) => [
                'kategori_id' => $i->kategori_id,
                'keterangan' => $i->keterangan,
                'nominal' => $i->nominal,
                'qty' => $i->qty,
                'no_pelanggan' => $i->no_pelanggan,
                'sn' => $i->sn,
            ]);
        @endphp
        const itemNota = @json($itemNotaUntukJs);

        const sumberItem = itemLama.length > 0 ? itemLama : itemNota;
        if (sumberItem.length > 0) {
            sumberItem.forEach(item => tambahItem(item));
        } else {
            tambahItem();
        }
        hitungTotal();
    </script>
    @include('nota._kompres-foto')
@endsection
