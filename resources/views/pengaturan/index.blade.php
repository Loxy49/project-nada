@extends('layouts.app')
@section('title', 'Template Nota')

@section('content')
    <div style="display:flex; justify-content:flex-end; align-items:center; margin-bottom:12px;">
        <a href="{{ route('pengaturan.create') }}" class="btn" style="white-space:nowrap;">+ Tambah template</a>
    </div>

    <div class="card">
        <table>
            <tr><th>Nama template</th><th>Nama toko</th><th>Status</th><th>Aksi</th></tr>
            @foreach($templates as $p)
                <tr>
                    <td>{{ $p->nama }}</td>
                    <td>{{ $p->nama_toko }}</td>
                    <td>
                        @if($p->is_default)
                            <span style="background:#E7F0E8; color:#5a7a62; font-size:12px; padding:3px 10px; border-radius:10px; font-weight:600;">Default</span>
                        @else
                            <form method="POST" action="{{ route('pengaturan.default', $p) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-outline" style="font-size:12px; padding:4px 10px;">Jadikan default</button>
                            </form>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px;">
                            <a href="{{ route('pengaturan.edit', $p) }}" class="btn btn-outline">Edit</a>
                            <form method="POST" action="{{ route('pengaturan.destroy', $p) }}" onsubmit="return confirm('Hapus template &quot;{{ $p->nama }}&quot;?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" @disabled($templates->count() <= 1)>Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection
