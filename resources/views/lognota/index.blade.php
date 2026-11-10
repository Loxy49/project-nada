@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
    <form method="GET" action="{{ route('lognota.index') }}" class="filter-bar">
        <select name="aksi">
            <option value="">Ubah, hapus &amp; pulihkan</option>
            <option value="semua" @selected(request('aksi') === 'semua')>Semua aksi (termasuk dibuat)</option>
            @foreach(\App\Models\NotaLog::LABEL as $kode => $nama)
                <option value="{{ $kode }}" @selected(request('aksi') === $kode)>{{ $nama }} saja</option>
            @endforeach
        </select>
        <select name="user_id">
            <option value="">Semua pengguna</option>
            @foreach($pengguna as $p)
                <option value="{{ $p->id }}" @selected((string) request('user_id') === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <input type="date" name="dari" value="{{ request('dari') }}" title="Dari tanggal">
        <input type="date" name="sampai" value="{{ request('sampai') }}" title="Sampai tanggal">
        <button type="submit" class="btn">Filter</button>
        <a href="{{ route('lognota.index') }}" class="btn btn-outline">Reset</a>
    </form>

    <p style="font-size:13px; color:#64748b; margin:0 0 10px;">
        Log Aktivitas mencatat siapa mengubah, menghapus, atau memulihkan nota, lengkap dengan kondisi sebelum dan sesudahnya.
        Untuk melihat daftar nota beserta foto bukti, buka menu Daftar Nota.
    </p>

    <div class="card">
        <table>
            <tr><th>Waktu</th><th>Nota</th><th>Total</th><th>Oleh</th><th>Aksi</th><th>Keterangan</th></tr>
            @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('nota.show', $log->nota_id) }}">{{ $log->no_nota }}</a></td>
                    <td style="white-space:nowrap;">{{ $log->total !== null ? 'Rp'.number_format($log->total, 0, ',', '.') : '-' }}</td>
                    <td>{{ $log->user->name ?? '-' }}</td>
                    <td>{{ $log->aksi_label }}</td>
                    <td style="font-size:13px;">@include('lognota._detail', ['log' => $log])</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada catatan.</td></tr>
            @endforelse
        </table>
    </div>

    {{ $logs->links() }}
@endsection
