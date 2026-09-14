<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Pengeluaran {{ $labelBulan }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #333333;
            font-size: 11px;
            margin: 0;
        }
        .kop {
            border-bottom: 2px solid #4b5563;
            margin-bottom: 14px;
        }
        .kop table { width: 100%; border-collapse: collapse; }
        .kop td { padding: 0 0 10px; vertical-align: bottom; }
        .kop .brand { font-size: 16px; font-weight: bold; text-align: left; }
        .kop .periode { font-size: 11px; text-align: right; color: #6b7280; }
        h1.judul {
            text-align: center;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 18px;
        }
        table.rekap { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.rekap th, table.rekap td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
            font-size: 10px;
        }
        table.rekap th { background: #F1F5F9; font-weight: bold; }
        table.rekap td.angka, table.rekap th.angka { text-align: right; white-space: nowrap; }
        table.rekap tfoot td { font-weight: bold; background: #F1F5F9; }

        .ringkasan-kategori { width: 60%; border-collapse: collapse; margin-bottom: 18px; }
        .ringkasan-kategori th, .ringkasan-kategori td {
            border: 1px solid #d1d5db; padding: 5px 8px; font-size: 10px;
        }
        .ringkasan-kategori th { background: #F1F5F9; text-align: left; }
        .ringkasan-kategori td.angka { text-align: right; }

        .footer-note { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="kop">
        <table>
            <tr>
                <td class="brand">{{ $pengaturan->nama_toko ?? 'NADA' }}</td>
                <td class="periode">
                    Rekap Pengeluaran Kas Kecil<br>
                    Periode: {{ $labelBulan }}<br>
                    Dicetak: {{ now()->format('d M Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <h1 class="judul">Rekap Pengeluaran Kas Kecil — {{ $labelBulan }}</h1>

    <table class="rekap">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Transaksi</th>
                <th>Kasir</th>
                <th>Kategori</th>
                <th>Metode Bayar</th>
                <th>Keterangan</th>
                <th class="angka">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notas as $i => $nota)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $nota->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $nota->no_nota }}</td>
                    <td>{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</td>
                    <td>{{ $nota->items->isNotEmpty() ? $nota->items->pluck('kategori.nama')->unique()->implode(', ') : ($nota->kategori->nama ?? '-') }}</td>
                    <td>{{ $nota->metode_bayar ?: '-' }}</td>
                    <td>{{ $nota->keterangan ?: '-' }}</td>
                    <td class="angka">Rp{{ number_format($nota->nominal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">TOTAL</td>
                <td class="angka">Rp{{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if($perKategori->isNotEmpty())
        <table class="ringkasan-kategori">
            <thead>
                <tr><th>Rekap per kategori</th><th class="angka">Nominal</th></tr>
            </thead>
            <tbody>
                @foreach($perKategori as $nama => $totalKategori)
                    <tr>
                        <td>{{ $nama }}</td>
                        <td class="angka">Rp{{ number_format($totalKategori, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-note">Dokumen ini dicetak otomatis oleh sistem {{ $pengaturan->nama_toko ?? 'NADA' }} untuk keperluan arsip &amp; rekonsiliasi tim accounting.</div>
</body>
</html>
