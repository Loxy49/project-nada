@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="card" style="background:linear-gradient(135deg, #84a98c 0%, #5a7a62 100%); color:#fff; border:none;">
        <h2 style="margin:0 0 4px; color:#fff;">Selamat datang, {{ explode(' ', auth()->user()->name)[0] }}</h2>
        <p style="margin:0; font-size:14px; color:rgba(255,255,255,0.9);">Catat setiap pengeluaran kas kecil agar mudah diarsipkan oleh tim accounting.</p>
    </div>

    <div class="dash-row">
        <div class="card" style="flex:1;">
            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5a7a62" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="3" width="16" height="18" rx="2"/>
                    <path d="M8 7h8M8 11h8M8 15h5"/>
                </svg>
                Ringkasan Pengeluaran
            </h3>
            <table>
                <tr><td>Total bulan ini</td><td style="text-align:right; font-weight:600;">Rp{{ number_format($ringkasan['total_bulan_ini'], 0, ',', '.') }}</td></tr>
                <tr><td>Nota bulan ini</td><td style="text-align:right; font-weight:600;">{{ $ringkasan['nota_bulan_ini'] }} nota</td></tr>
                <tr><td>Total nota (semua waktu)</td><td style="text-align:right; font-weight:600;">{{ $ringkasan['total_nota'] }} nota</td></tr>
            </table>
        </div>

        <div class="card" style="flex:1; display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d4a017" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2.5h9l2.5 2.5v16.5l-3-1.6-3 1.6-3-1.6-3 1.6V2.5z"/>
                        <line x1="8.3" y1="7.5" x2="15.5" y2="7.5"/>
                        <line x1="8.3" y1="11.5" x2="15.5" y2="11.5"/>
                    </svg>
                    Catat Pengeluaran Baru
                </h3>
                <p style="font-size:13.5px; color:#6b7280; margin:0 0 16px;">Catat pembelian seperti parkir, konsumsi rapat, atau alat tulis kantor segera setelah transaksi dilakukan.</p>
            </div>
            <a href="{{ route('nota.create') }}" class="btn" style="text-align:center;">+ Buat nota baru</a>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Nota Terbaru</h3>
        <table>
            <tr><th>No. nota</th><th>Kategori</th><th>Nominal</th></tr>
            @forelse($notaSaya as $nota)
                <tr>
                    <td><a href="{{ route('nota.show', $nota) }}">{{ $nota->no_nota }}</a></td>
                    <td>{{ $nota->kategori->nama }}</td>
                    <td>Rp{{ number_format($nota->nominal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; padding:28px 8px;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c9b18f" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px;">
                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                            <path d="M8 8h8M8 12h8M8 16h5"/>
                        </svg>
                        <div style="font-weight:600; margin-bottom:4px;">Belum ada nota</div>
                        <div style="font-size:13px; color:#94A3B8; margin-bottom:12px;">Belum ada nota. Pencatatan satu nota hanya memerlukan waktu kurang dari satu menit.</div>
                        <a href="{{ route('nota.create') }}" class="btn" style="font-size:13px; padding:7px 16px;">+ Buat nota</a>
                    </td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection