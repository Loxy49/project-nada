@extends('layouts.app')
@section('title', 'Log Nota')

@section('content')
    <form method="GET" action="{{ route('lognota.index') }}" class="filter-bar">
        <select name="aksi">
            <option value="">Semua aksi</option>
            @foreach(\App\Models\NotaLog::LABEL as $kode => $nama)
                <option value="{{ $kode }}" @selected(request('aksi') === $kode)>{{ $nama }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn">Filter</button>
        <a href="{{ route('lognota.index') }}" class="btn btn-outline">Reset</a>
    </form>

    <div class="card">
        <table>
            <tr><th>Waktu</th><th>Nota</th><th>Oleh</th><th>Aksi</th><th>Keterangan</th></tr>
            @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('nota.show', $log->nota_id) }}">{{ $log->no_nota }}</a></td>
                    <td>{{ $log->user->name ?? '-' }}</td>
                    <td>{{ $log->aksi_label }}</td>
                    <td style="font-size:13px;">
                        @if($log->alasan)<div>Alasan: {{ $log->alasan }}</div>@endif
                        @if($log->aksi === 'ubah')
                            @foreach($log->perubahan() as $baris)
                                <div style="color:#475569;">{{ $baris }}</div>
                            @endforeach
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada catatan.</td></tr>
            @endforelse
        </table>
    </div>

    {{ $logs->links() }}
@endsection
