@extends('layouts.app')
@section('title', 'Detail Nota')

@section('content')
    <div class="card" style="max-width:560px; margin-left:auto; margin-right:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="margin:0;">{{ $nota->no_nota }}</h3>
            <div style="display:flex; gap:8px;">
                @if($nota->trashed())
                    <span style="font-size:12px; font-weight:600; color:#991b1b; background:#fee2e2; padding:6px 10px; border-radius:6px; align-self:center;">Dihapus</span>
                @elseif($nota->terkunci)
                    <span title="Periode sudah ditutup accounting" style="font-size:12px; font-weight:600; color:#991b1b; background:#fee2e2; padding:6px 10px; border-radius:6px; align-self:center; display:inline-flex; align-items:center; gap:5px;"><x-ikon nama="kunci" :ukuran="13" /> {{ auth()->user()->isAdmin() ? 'Periode ditutup' : 'Sudah diarsipkan' }}</span>
                @elseif($nota->bisaDiedit(auth()->user()))
                    <a href="{{ route('nota.edit', $nota) }}" class="btn btn-outline" style="font-size:13px; padding:6px 12px;">Edit</a>
                @endif
                @unless($nota->trashed())
                    <a href="{{ route('nota.cetak', $nota) }}" target="_blank" class="btn btn-outline" style="font-size:13px; padding:6px 12px;"><x-ikon nama="cetak" :ukuran="14" />Cetak nota</a>
                @endunless
            </div>
        </div>
        @if($nota->trashed())
            <div class="alert alert-error" style="margin-bottom:12px;">
                Nota ini dihapus pada {{ $nota->deleted_at->format('d M Y H:i') }} oleh {{ $nota->penghapus->name ?? '-' }}.
                Alasan: {{ $nota->alasan_hapus ?: '-' }}
            </div>
        @endif
        <table>
            <tr><td>No. Transaksi</td><td style="text-align:right;">{{ $nota->no_nota }}</td></tr>
            <tr><td>Tanggal</td><td style="text-align:right;">{{ $nota->tanggal->format('d M Y') }}</td></tr>
            <tr><td>Kasir</td><td style="text-align:right;">{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</td></tr>
            @if($nota->metode_bayar)
                <tr><td>Metode bayar</td><td style="text-align:right;">{{ $nota->metode_bayar }}</td></tr>
            @endif
        </table>

        @if($nota->items->isNotEmpty())
            <label style="display:block; margin:14px 0 6px; font-weight:600; font-size:13px;">Rincian item</label>
            <table>
                <tr>
                    <th>Kategori</th><th>Keterangan</th>
                    <th style="text-align:right;">Harga</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Subtotal</th>
                </tr>
                @foreach($nota->items as $item)
                    <tr>
                        <td>{{ $item->kategori->nama }}</td>
                        <td>
                            {{ $item->keterangan ?: '-' }}
                            @if($item->no_pelanggan)
                                <div style="font-size:12px; color:#6b7280;">No. Pelanggan: {{ $item->no_pelanggan }}</div>
                            @endif
                            @if($item->sn)
                                <div style="font-size:12px; color:#6b7280;">SN: {{ $item->sn }}</div>
                            @endif
                        </td>
                        <td style="text-align:right;">Rp{{ number_format($item->nominal, 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ $item->qty }}</td>
                        <td style="text-align:right;">Rp{{ number_format($item->nominal * $item->qty, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4" style="font-weight:600;">Total</td>
                    <td style="text-align:right; font-weight:600;">Rp{{ number_format($nota->nominal, 0, ',', '.') }}</td>
                </tr>
            </table>
        @else
            <table>
                <tr><td>Kategori</td><td style="text-align:right;">{{ $nota->kategori->nama }}</td></tr>
                <tr><td>Keterangan</td><td style="text-align:right;">{{ $nota->keterangan ?: '-' }}</td></tr>
                <tr><td>Nominal</td><td style="text-align:right; font-weight:600;">Rp{{ number_format($nota->nominal, 0, ',', '.') }}</td></tr>
            </table>
        @endif

        <div style="margin-top:18px;">
            <div style="font-size:13px; font-weight:600; margin-bottom:6px;">Bukti pendukung</div>
            @if($nota->bukti_file)
                <a href="{{ route('nota.bukti', $nota) }}" target="_blank" rel="noopener" title="Klik untuk membuka ukuran penuh">
                    <img src="{{ route('nota.bukti', $nota) }}" alt="Foto bukti nota {{ $nota->no_nota }}" style="max-width:100%; max-height:380px; border:1px solid #e5e7eb; border-radius:8px;">
                </a>
                <div style="font-size:12px; color:#94A3B8; margin-top:4px;">Klik foto untuk membuka ukuran penuh.</div>
            @else
                <div style="font-size:13px; color:#94A3B8;">Tidak ada foto bukti pada nota ini.</div>
            @endif
        </div>
    </div>

    @if(auth()->user()->isAdmin())
        @if($nota->trashed())
            <div class="card" style="max-width:560px; margin:16px auto 0;">
                @if($nota->terkunci)
                    <p style="margin:0; font-size:13px; color:#64748b;">Periode nota ini sedang terkunci sehingga nota belum dapat dipulihkan. Buka kunci periode terlebih dahulu melalui menu Kunci Periode.</p>
                @else
                    <form method="POST" action="{{ route('nota.pulihkan', $nota->id) }}">
                        @csrf
                        <button type="submit" class="btn">Pulihkan nota</button>
                    </form>
                @endif
            </div>
        @elseif(! $nota->terkunci)
            <div class="card" id="hapus" style="max-width:560px; margin:16px auto 0;">
                <h4 style="margin-top:0;">Hapus nota</h4>
                <p style="font-size:13px; color:#64748b; margin:0 0 10px;">Nota tidak dihapus permanen: nota dapat dipulihkan melalui Riwayat → Nota terhapus dan tercatat di Log Nota. Alasan wajib diisi.</p>
                <form method="POST" action="{{ route('nota.destroy', $nota) }}" style="display:flex; gap:8px; flex-wrap:wrap;">
                    @csrf @method('DELETE')
                    <input type="text" name="alasan" value="{{ old('alasan') }}" placeholder="Alasan menghapus nota (min. 5 karakter)" required minlength="5" maxlength="255" style="flex:1; min-width:220px;">
                    <button type="submit" class="btn btn-danger">Hapus nota</button>
                </form>
                @error('alasan') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
        @endif

        <div class="card" style="max-width:560px; margin:16px auto 0;">
            <h4 style="margin-top:0;">Riwayat perubahan</h4>
            @forelse($logs as $log)
                <div style="padding:8px 0; border-top:1px solid #eef0ee; font-size:13px;">
                    <strong>{{ $log->aksi_label }}</strong> oleh {{ $log->user->name ?? '-' }}
                    <span style="color:#94A3B8;">· {{ $log->created_at->format('d M Y H:i') }}</span>
                    @if($log->alasan)<div>Alasan: {{ $log->alasan }}</div>@endif
                    @if($log->aksi === 'ubah')
                        @foreach($log->perubahan() as $baris)
                            <div style="color:#475569;">{{ $baris }}</div>
                        @endforeach
                    @endif
                </div>
            @empty
                <p style="margin:0; font-size:13px; color:#94A3B8;">Belum ada catatan untuk nota ini.</p>
            @endforelse
        </div>
    @endif
@endsection
