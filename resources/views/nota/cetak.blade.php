<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak {{ $nota->no_nota }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Segoe UI', sans-serif;
            background: #f7f6f2;
            color: #4b5563;
            margin: 0;
            padding: 24px;
        }

        /* ===== Toolbar (tidak ikut tercetak) ===== */
        .toolbar {
            max-width: 720px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .toolbar a { color: #5a7a62; text-decoration: none; font-size: 14px; }
        .switcher { display: inline-flex; background: #e5e4e2; border-radius: 8px; padding: 3px; }
        .switcher button {
            border: none; background: transparent; padding: 6px 14px; font-size: 13px;
            border-radius: 6px; cursor: pointer; color: #4b5563; font-weight: 500;
        }
        .switcher button.active { background: #5a7a62; color: #fff; }
        .btn {
            display: inline-block; background: #5a7a62; color: #fff; border: none;
            padding: 8px 18px; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none;
        }

        /* ===== Kertas ===== */
        .kertas {
            display: none;
            background: #fff;
            margin: 0 auto;
            border-radius: 10px;
            border: 1px solid #e5e4e2;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .kertas.active { display: block; }

        /* --- Formal A4/A5 (pengaturan awal) --- */
        #formal.kertas {
            max-width: {{ $pengaturan->orientasi === 'lanskap' ? '980px' : '720px' }};
            padding: 40px 48px;
        }
        #formal .kop {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 2px solid #4b5563; padding-bottom: 14px; margin-bottom: 18px;
        }
        #formal .kop .brand { font-size: 20px; font-weight: 700; }
        #formal .kop .sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
        #formal .kop .meta { text-align: right; font-size: 13px; }
        #formal h2.judul {
            text-align: center; text-transform: uppercase; letter-spacing: 0.5px;
            font-size: 17px; margin: 0 0 18px;
        }
        #formal table.rincian { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 4px; }
        #formal table.rincian td { padding: 7px 4px; vertical-align: top; }
        #formal table.rincian td:first-child { width: 170px; color: #4b5563; }
        #formal table.rincian td:nth-child(2) { width: 14px; color: #4b5563; }
        #formal .nominal-box {
            background: #f7f6f2; border-radius: 8px; padding: 14px 16px; margin: 16px 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        #formal .nominal-box .label { font-size: 12px; color: #6b7280; }
        #formal .nominal-box .angka { font-size: 22px; font-weight: 700; }
        #formal .terbilang { font-size: 12.5px; font-style: italic; color: #4b5563; margin-bottom: 18px; }
        .badge { font-size: 12px; padding: 3px 11px; border-radius: 10px; font-weight: 600; }
        .badge-menunggu { background: #f7f6f2; color: #6b7280; }
        .badge-disetujui { background: #E7F0E8; color: #5a7a62; }
        .badge-ditolak { background: #FBEAEA; color: #B91C1C; }
        #formal .ttd { display: flex; justify-content: space-between; margin-top: 48px; }
        #formal .ttd .kotak { width: 45%; text-align: center; font-size: 13px; }
        #formal .ttd .garis { margin-top: 56px; border-top: 1px solid #4b5563; padding-top: 6px; font-weight: 600; }
        #formal .footer-note { margin-top: 32px; font-size: 11px; color: #a3a3a3; text-align: center; }

        /* --- Struk: kartu rounded ala nota digital --- */
        #struk.kertas {
            max-width: {{ $pengaturan->orientasi === 'lanskap' ? '600px' : '380px' }};
            padding: 30px 26px;
            border-radius: 20px;
        }
        #struk .icon-box {
            width: 44px; height: 44px; margin: 0 auto 14px;
            background: #E7F0E8; color: #5a7a62;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
        }
        #struk .company-name { font-size: 15.5px; font-weight: 700; text-align: center; }
        #struk .company-sub { font-size: 11.5px; color: #6b7280; text-align: center; line-height: 1.5; }
        #struk .divider { border: none; border-top: 1px dashed #a3a3a3; margin: 12px 0; }
        #struk .judul {
            text-align: center; font-weight: 700; font-size: 14.5px; margin-bottom: 16px;
        }
        #struk .baris-struk {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13.5px; padding: 6px 0;
        }
        #struk .baris-struk .k { color: #6b7280; }
        #struk .baris-struk .v { font-weight: 500; text-align: right; }
        #struk .baris-struk.total { padding-top: 10px; }
        #struk .baris-struk.total .k { font-weight: 700; color: #4b5563; font-size: 14.5px; }
        #struk .baris-struk.total .v { font-weight: 700; font-size: 16.5px; }
        #struk .footer-note { margin-top: 4px; font-size: 11.5px; color: #a3a3a3; text-align: center; line-height: 1.6; }

        @media print {
            @page { size: A4 {{ $pengaturan->orientasi === 'lanskap' ? 'landscape' : 'portrait' }}; margin: 10mm; }
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .kertas { box-shadow: none; border: none; border-radius: 0; margin: 0 auto; }
            #formal.kertas { padding: 10mm 14mm; max-width: 100%; }
            #struk.kertas { max-width: 95mm; padding: 8mm 7mm; }
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <a href="{{ route('nota.show', $nota) }}">&larr; Kembali ke detail nota</a>

        @if($daftarTemplate->count() > 1)
            <form method="GET" action="{{ route('nota.cetak', $nota) }}" style="margin:0;">
                <select name="template" onchange="this.form.submit()" style="font-size:13px; padding:6px 10px; border-radius:6px; border:1px solid #e5e4e2;">
                    @foreach($daftarTemplate as $t)
                        <option value="{{ $t->id }}" @selected($pengaturan->id === $t->id)>{{ $t->nama }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <div class="switcher">
            <button type="button" id="btn-formal" onclick="pilihFormat('formal')">Formal (A4)</button>
            <button type="button" id="btn-struk" onclick="pilihFormat('struk')">Struk</button>
        </div>
        <button class="btn" onclick="window.print()" style="display:inline-flex; align-items:center; gap:6px;"><x-ikon nama="cetak" :ukuran="15" /> Cetak</button>
    </div>

    {{-- ====== FORMAT FORMAL (A4/A5) — pengaturan awal ====== --}}
    <div id="formal" class="kertas">
        <div class="kop">
            <div>
                <div class="brand">
                    @if($pengaturan->logo)
                        <img src="{{ asset('storage/'.$pengaturan->logo) }}" alt="Logo" style="height:22px; vertical-align:middle; margin-right:6px; border-radius:4px;">
                    @else
                        <x-ikon nama="nota" :ukuran="18" style="vertical-align:-3px; margin-right:4px; color:#5a7a62;" />
                    @endif
                    {{ $pengaturan->nama_toko }}
                </div>
                <div class="sub">Bukti Pengeluaran Kas Kecil</div>
            </div>
            <div class="meta">
                <div><strong>{{ $nota->no_nota }}</strong></div>
                <div>Dicetak pada tanggal:</div>
                <div><span class="tgl-cetak-live">{{ now()->format('d M Y') }}</span></div>
                <div><span class="jam-cetak-only">{{ now()->format('H:i:s') }}</span></div>
            </div>
        </div>

        <h2 class="judul">Bukti Pengeluaran Kas Kecil</h2>

        <table class="rincian">
            <tr><td>No. Transaksi</td><td>:</td><td>{{ $nota->no_nota }}</td></tr>
            <tr><td>Tanggal Transaksi</td><td>:</td><td>{{ $nota->tanggal->format('d M Y') }}</td></tr>
            <tr><td>Kasir</td><td>:</td><td>{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</td></tr>
            @if($nota->metode_bayar)
                <tr><td>Metode Bayar</td><td>:</td><td>{{ $nota->metode_bayar }}</td></tr>
            @endif
            @if($nota->items->count() <= 1)
                @php $item = $nota->items->first(); @endphp
                <tr><td>Kategori</td><td>:</td><td>{{ $item->kategori->nama ?? $nota->kategori->nama }}</td></tr>
                <tr><td>Keterangan</td><td>:</td><td>{{ ($item->keterangan ?? $nota->keterangan) ?: '-' }}</td></tr>
                @if($item)
                    <tr><td>Qty</td><td>:</td><td>{{ $item->qty }}</td></tr>
                    @if($item->no_pelanggan)
                        <tr><td>No. Pelanggan</td><td>:</td><td>{{ $item->no_pelanggan }}</td></tr>
                    @endif
                    @if($item->sn)
                        <tr><td>SN</td><td>:</td><td>{{ $item->sn }}</td></tr>
                    @endif
                @endif
            @endif
        </table>

        @if($nota->items->count() > 1)
            <table class="rincian" style="margin-top:6px;">
                <tr>
                    <td colspan="2" style="font-weight:600; padding-bottom:6px; border-bottom:1px solid #e5e4e2;">Rincian item</td>
                    <td style="font-weight:600; padding-bottom:6px; border-bottom:1px solid #e5e4e2; text-align:left;">Qty</td>
                    <td style="font-weight:600; padding-bottom:6px; border-bottom:1px solid #e5e4e2; text-align:right;">Subtotal</td>
                </tr>
                @foreach($nota->items as $item)
                    <tr>
                        <td colspan="2">
                            {{ $item->kategori->nama }}
                            @if($item->keterangan)
                                <div style="font-size:11.5px; color:#a3a3a3; font-weight:400;">{{ $item->keterangan }}</div>
                            @endif
                            @if($item->qty > 1)
                                <div style="font-size:11.5px; color:#a3a3a3; font-weight:400;">Harga satuan: Rp{{ number_format($item->nominal, 0, ',', '.') }}</div>
                            @endif
                            @if($item->no_pelanggan)
                                <div style="font-size:11.5px; color:#a3a3a3; font-weight:400;">No. Pelanggan: {{ $item->no_pelanggan }}</div>
                            @endif
                            @if($item->sn)
                                <div style="font-size:11.5px; color:#a3a3a3; font-weight:400;">SN: {{ $item->sn }}</div>
                            @endif
                        </td>
                        <td style="text-align:left; font-weight:700;">{{ $item->qty }}</td>
                        <td style="text-align:right; white-space:nowrap;">Rp{{ number_format($item->nominal * $item->qty, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <div class="nominal-box">
            <span class="label">Total Nominal</span>
            <span class="angka">Rp{{ number_format($nota->nominal, 0, ',', '.') }}</span>
        </div>
        <div class="terbilang">Terbilang: {{ $terbilang }}</div>

        <div class="ttd">
            <div class="kotak">
                <div>Kasir,</div>
                <div class="garis">{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</div>
            </div>
            <div class="kotak">
                <div>Mengetahui,</div>
                <div class="garis">{{ $nota->approver->name ?? '(............................)' }}</div>
            </div>
        </div>

        <div class="footer-note">Dokumen ini dicetak otomatis oleh sistem {{ $pengaturan->nama_toko }} dan sah tanpa cap basah selama status tercatat pada sistem.</div>
    </div>

    {{-- ====== FORMAT STRUK ====== --}}
    <div id="struk" class="kertas">
        <div class="icon-box">
            @if($pengaturan->logo)
                <img src="{{ asset('storage/'.$pengaturan->logo) }}" alt="Logo" style="width:100%; height:100%; object-fit:cover; border-radius:13px;">
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
                    <path d="M6 2.5h9l2.5 2.5v16.5l-3-1.6-3 1.6-3-1.6-3 1.6V2.5z"/>
                    <line x1="8.3" y1="7.5" x2="15.5" y2="7.5"/>
                    <line x1="8.3" y1="11.5" x2="15.5" y2="11.5"/>
                    <line x1="8.3" y1="15.5" x2="13" y2="15.5"/>
                </svg>
            @endif
        </div>
        <div class="company-name">{{ $pengaturan->nama_toko }}</div>
        @if($pengaturan->alamat)
            <div class="company-sub">{{ $pengaturan->alamat }}</div>
        @endif
        @if($pengaturan->no_cs)
            <div class="company-sub">CS: {{ $pengaturan->no_cs }}</div>
        @endif

        <hr class="divider">
        <div class="judul">Bukti nota pengeluaran</div>
        <div style="text-align:center; font-size:11.5px; color:#6b7280; margin-top:-8px; margin-bottom:8px;">No. Transaksi: {{ $nota->no_nota }}</div>

        <hr class="divider">

        <div style="display:flex; justify-content:space-between; font-size:12.5px;">
            <div style="text-align:left; line-height:1.7;">
                <div>Dicetak pada tanggal:</div>
                <div><span class="tgl-cetak-live">{{ now()->format('d M Y') }}</span></div>
                <div><span class="jam-cetak-only">{{ now()->format('H:i:s') }}</span></div>
            </div>
            <div style="text-align:right; line-height:1.7;">
                <div>Kasir:</div>
                <div>{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</div>
            </div>
        </div>

        <hr class="divider">

        @php $daftarItem = $nota->items->isNotEmpty() ? $nota->items : collect([null]); @endphp

        @foreach($daftarItem as $i => $item)
            <div class="baris-struk" style="align-items:flex-start;">
                <span class="k" style="max-width:65%;">
                    {{ $i + 1 }}. {{ $item->kategori->nama ?? $nota->kategori->nama }}
                    @if($item->keterangan ?? $nota->keterangan)
                        <div style="font-size:11px; color:#a3a3a3;">{{ $item->keterangan ?? $nota->keterangan }}</div>
                    @endif
                    @if($item && $item->qty > 1)
                        <div style="font-size:11px; color:#a3a3a3;">{{ $item->qty }} x Rp{{ number_format($item->nominal, 0, ',', '.') }}</div>
                    @endif
                    @if($item && $item->no_pelanggan)
                        <div style="font-size:11px; color:#a3a3a3;">No. Pelanggan: {{ $item->no_pelanggan }}</div>
                    @endif
                    @if($item && $item->sn)
                        <div style="font-size:11px; color:#a3a3a3;">SN: {{ $item->sn }}</div>
                    @endif
                </span>
                <span class="v">Rp{{ number_format(($item->nominal ?? $nota->nominal) * ($item->qty ?? 1), 0, ',', '.') }}</span>
            </div>
        @endforeach

        <hr class="divider">

        <div style="text-align:left; font-weight:700; font-size:13px; margin:10px 0;">
            Qty : {{ $daftarItem->sum(fn($i) => $i->qty ?? 1) }}
        </div>

        <hr class="divider">

        @if($nota->metode_bayar)
            <div class="baris-struk"><span class="k">Metode Bayar</span><span class="v">{{ $nota->metode_bayar }}</span></div>
        @endif
        <div class="baris-struk"><span class="k">Sub Total</span><span class="v">Rp{{ number_format($nota->nominal, 0, ',', '.') }}</span></div>
        <div class="baris-struk total"><span class="k">Total</span><span class="v">Rp{{ number_format($nota->nominal, 0, ',', '.') }}</span></div>

        <hr class="divider">

        <div class="footer-note">{{ $pengaturan->teks_footer }}</div>
    </div>

    <script>
        function pilihFormat(mode) {
            document.getElementById('formal').classList.toggle('active', mode === 'formal');
            document.getElementById('struk').classList.toggle('active', mode === 'struk');
            document.getElementById('btn-formal').classList.toggle('active', mode === 'formal');
            document.getElementById('btn-struk').classList.toggle('active', mode === 'struk');
            localStorage.setItem('pettynota_cetak_mode', mode);
        }
        pilihFormat(localStorage.getItem('pettynota_cetak_mode') || '{{ $pengaturan->gaya_default }}');

        // Jam cetak mengikuti jam yang benar-benar berjalan saat ini (live clock),
        // bukan cuma waktu saat halaman pertama kali dimuat di server.
        const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        function perbaruiJamCetak() {
            const now = new Date();
            const tgl = String(now.getDate()).padStart(2, '0');
            const bln = namaBulan[now.getMonth()];
            const thn = now.getFullYear();
            const jam = String(now.getHours()).padStart(2, '0');
            const mnt = String(now.getMinutes()).padStart(2, '0');
            const dtk = String(now.getSeconds()).padStart(2, '0');
            const teksTanggal = `${tgl} ${bln} ${thn}`;
            const teksJam = `${jam}:${mnt}:${dtk}`;
            document.querySelectorAll('.tgl-cetak-live').forEach(el => el.textContent = teksTanggal);
            document.querySelectorAll('.jam-cetak-only').forEach(el => el.textContent = teksJam);
        }
        perbaruiJamCetak();
        setInterval(perbaruiJamCetak, 1000);
    </script>
</body>
</html>
