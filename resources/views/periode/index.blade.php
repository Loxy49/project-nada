@extends('layouts.app')
@section('title', 'Kunci Periode')

@section('content')
    <style>
        .badge-kunci { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .badge-kunci.terkunci { background:#fee2e2; color:#991b1b; }
        .badge-kunci.terbuka { background:#e5f0e8; color:#3f6149; }
        .badge-kunci.berjalan { background:#f1f5f9; color:#64748b; }
        html[data-theme="dark"] .badge-kunci.terkunci { background:var(--error-bg); color:var(--error-text); }
        html[data-theme="dark"] .badge-kunci.terbuka { background:var(--success-bg); color:var(--success-text); }
        html[data-theme="dark"] .badge-kunci.berjalan { background:var(--hover); color:var(--muted); }
        .periode-aksi { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .periode-aksi form { margin:0; display:inline-flex; }
        .periode-aksi .btn, .periode-aksi a.btn { padding:5px 12px; font-size:12px; }
        .periode-aksi input[type=text] { padding:5px 8px; font-size:12px; border:1px solid #d1d5db; border-radius:6px; width:200px; max-width:100%; }
        .periode-aksi form { gap:6px; flex-wrap:wrap; }
    </style>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="margin-bottom:16px;">
        <p style="margin:0; font-size:13.5px; color:#475569; line-height:1.6;">
            Kunci bulan yang sudah selesai direkap. Nota yang tanggalnya jatuh di bulan terkunci <strong>tidak bisa ditambah, diubah, dihapus, atau dipulihkan</strong> oleh siapa pun, termasuk admin.
            Nota tetap dapat dicetak ulang. Jika perlu koreksi, buka kunci periode terlebih dahulu (alasan wajib diisi dan akan tercatat), lalu kunci kembali.
        </p>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <table>
            <tr>
                <th>Periode</th><th>Nota</th><th>Total</th><th>Status</th><th>Aksi</th>
            </tr>
            @foreach($baris as $b)
                <tr>
                    <td><strong>{{ $b['label'] }}</strong></td>
                    <td>{{ $b['jumlah'] }}</td>
                    <td>Rp{{ number_format($b['total'], 0, ',', '.') }}</td>
                    <td>
                        @if($b['kunci'])
                            <span class="badge-kunci terkunci"><x-ikon nama="kunci" :ukuran="12" style="vertical-align:-2px; margin-right:2px;" /> Terkunci</span>
                            <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                {{ $b['kunci']->dikunci_pada->format('d M Y H:i') }} oleh {{ $b['kunci']->pengunci->name ?? '-' }}
                                @if($b['kunci']->catatan)<br>“{{ $b['kunci']->catatan }}”@endif
                            </div>
                        @elseif($b['bisa_dikunci'])
                            <span class="badge-kunci terbuka">Terbuka</span>
                        @else
                            <span class="badge-kunci berjalan">Bulan berjalan</span>
                        @endif
                    </td>
                    <td>
                        <div class="periode-aksi">
                            <a href="{{ route('rekap.export.excel', ['bulan' => $b['key']]) }}" class="btn btn-outline">Excel</a>
                            <a href="{{ route('rekap.export.pdf', ['bulan' => $b['key']]) }}" class="btn btn-outline">PDF</a>

                            @if($b['kunci'])
                                <form method="POST" action="{{ route('periode.buka', $b['kunci']) }}">
                                    @csrf
                                    <input type="text" name="alasan" placeholder="Alasan buka kunci (min. 5 karakter)" required minlength="5" maxlength="255">
                                    <button type="submit" class="btn btn-danger">Buka kunci</button>
                                </form>
                            @elseif($b['bisa_dikunci'])
                                <form method="POST" action="{{ route('periode.kunci') }}">
                                    @csrf
                                    <input type="hidden" name="bulan" value="{{ $b['key'] }}">
                                    <input type="text" name="catatan" placeholder="Catatan (opsional)" maxlength="255">
                                    <button type="submit" class="btn">Kunci</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="card">
        <h4 style="margin-top:0;">Riwayat kunci &amp; buka kunci</h4>
        @if($riwayat->isEmpty())
            <p style="margin:0; font-size:13px; color:#94A3B8;">Belum ada periode yang dikunci.</p>
        @else
            <table>
                <tr><th>Periode</th><th>Dikunci</th><th>Dibuka</th></tr>
                @foreach($riwayat as $r)
                    <tr>
                        <td>{{ $r->label }}</td>
                        <td style="font-size:13px;">
                            {{ $r->dikunci_pada->format('d M Y H:i') }}<br>
                            <span style="color:#64748b;">{{ $r->pengunci->name ?? '-' }}@if($r->catatan) — “{{ $r->catatan }}”@endif</span>
                        </td>
                        <td style="font-size:13px;">
                            @if($r->dibuka_pada)
                                {{ $r->dibuka_pada->format('d M Y H:i') }}<br>
                                <span style="color:#64748b;">{{ $r->pembuka->name ?? '-' }} — “{{ $r->alasan_buka }}”</span>
                            @else
                                <span style="color:#991b1b;">Masih terkunci</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection
