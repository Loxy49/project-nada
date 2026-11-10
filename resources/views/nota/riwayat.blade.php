@extends('layouts.app')
@section('title', $terhapus ? 'Nota Terhapus' : 'Daftar Nota')

@section('content')
    <form method="GET" action="{{ route('nota.riwayat') }}" class="filter-bar">
        <input type="hidden" name="cari" value="{{ request('cari') }}">
        @if($terhapus)<input type="hidden" name="terhapus" value="1">@endif
        <select name="kategori_id">
            <option value="">Semua kategori</option>
            @foreach($kategori as $k)
                <option value="{{ $k->id }}" @selected((string) request('kategori_id') === (string) $k->id)>{{ $k->nama }}</option>
            @endforeach
        </select>
        <input type="month" name="bulan" value="{{ request('bulan') }}">
        <button type="submit" class="btn">Filter</button>
        <a href="{{ route('nota.riwayat', $terhapus ? ['terhapus' => 1] : []) }}" class="btn btn-outline">Reset</a>
        @if(auth()->user()->isAdmin())
            @if($terhapus)
                <a href="{{ route('nota.riwayat') }}" class="btn btn-outline">← Kembali ke daftar nota</a>
            @else
                <a href="{{ route('nota.riwayat', ['terhapus' => 1]) }}" class="btn btn-outline">Nota terhapus</a>
            @endif
        @endif
    </form>

    <div class="card">
        <table>
            <tr>
                <th>No. nota</th>
                @if(auth()->user()->isAdmin())<th>Kasir</th>@endif
                <th>Kategori</th><th>Tanggal</th><th>Nominal</th>
                @if($terhapus)<th>Dihapus</th>@endif
                <th></th>
            </tr>
            @forelse($riwayat as $nota)
                <tr>
                    <td><a href="{{ route('nota.show', $nota) }}">{{ $nota->no_nota }}</a></td>
                    @if(auth()->user()->isAdmin())<td>{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</td>@endif
                    <td>{{ $nota->kategori->nama }}</td>
                    <td>{{ $nota->tanggal->format('d M Y') }}</td>
                    <td>Rp{{ number_format($nota->nominal, 0, ',', '.') }}</td>
                    @if($terhapus)
                        <td style="font-size:12px;">
                            {{ $nota->deleted_at->format('d M Y H:i') }} oleh {{ $nota->penghapus->name ?? '-' }}
                            <div style="color:#64748b;">{{ $nota->alasan_hapus }}</div>
                        </td>
                    @endif
                    <td style="white-space:nowrap;">
                        {{-- Urutan: status/Edit, Cetak, Bukti, lalu aksi berbahaya (Hapus/Pulihkan) paling akhir. --}}
                        @if($terhapus)
                            @if($nota->bukti_file)
                                <a href="{{ route('nota.bukti', $nota) }}" target="_blank" rel="noopener" class="aksi"><x-ikon nama="foto" :ukuran="14" /> Bukti</a>
                            @endif
                            @if($nota->terkunci)
                                <span title="Periode sudah ditutup accounting" class="status-kunci"><x-ikon nama="kunci" :ukuran="12" /> Ditutup</span>
                            @else
                                <form method="POST" action="{{ route('nota.pulihkan', $nota->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn" style="padding:4px 10px; font-size:12px;">Pulihkan</button>
                                </form>
                            @endif
                        @else
                            @if($nota->terkunci)
                                <span title="Periode sudah ditutup accounting" class="status-kunci" style="margin-right:12px;"><x-ikon nama="kunci" :ukuran="12" /> {{ auth()->user()->isAdmin() ? 'Ditutup' : 'Diarsipkan' }}</span>
                            @elseif($nota->bisaDiedit(auth()->user()))
                                <a href="{{ route('nota.edit', $nota) }}" class="aksi"><x-ikon nama="edit" :ukuran="14" /> Edit</a>
                            @endif
                            <a href="{{ route('nota.cetak', $nota) }}" target="_blank" class="aksi"><x-ikon nama="cetak" :ukuran="14" /> Cetak</a>
                            {{-- Foto bukti dibuka di tab baru lewat route aplikasi (cek login & hak akses). --}}
                            @if($nota->bukti_file)
                                <a href="{{ route('nota.bukti', $nota) }}" target="_blank" rel="noopener" class="aksi"><x-ikon nama="foto" :ukuran="14" /> Bukti</a>
                            @endif
                            @if(auth()->user()->isAdmin() && ! $nota->terkunci)
                                <a href="{{ route('nota.show', $nota) }}#hapus" class="aksi aksi-bahaya"><x-ikon nama="hapus" :ukuran="14" /> Hapus</a>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $terhapus ? 7 : 6 }}">{{ $terhapus ? 'Tidak ada nota terhapus.' : 'Tidak ada nota yang cocok.' }}</td></tr>
            @endforelse
        </table>
    </div>

    {{ $riwayat->links() }}
@endsection
