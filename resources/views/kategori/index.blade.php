@extends('layouts.app')
@section('title', 'Kategori')

@section('content')
    <style>
        .kategori-table { table-layout: fixed; }
        .kategori-table col.col-aksi { width: 190px; }
        .aksi-group { display: inline-flex; align-items: center; gap: 8px; }
        .aksi-group form { display: inline-flex; margin: 0; }
        .kategori-table .btn { padding: 7px 14px; font-size: 13px; border-radius: 6px; line-height: 1.2; }
        @media (max-width: 480px) {
            .kategori-table { table-layout: auto; }
        }
    </style>

    <a href="{{ route('kategori.create') }}" class="btn" style="margin-bottom:12px; display:inline-block;">+ Tambah kategori</a>

    <div class="card">
        <table class="kategori-table">
            <colgroup>
                <col>
                <col class="col-aksi">
            </colgroup>
            <tr><th>Nama</th><th class="col-aksi">Aksi</th></tr>
            @foreach($kategori as $k)
                <tr>
                    <td>{{ $k->nama }}</td>
                    <td class="col-aksi">
                        <div class="aksi-group">
                            <a href="{{ route('kategori.edit', $k) }}" class="btn btn-outline">Edit</a>
                            <form method="POST" action="{{ route('kategori.destroy', $k) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    {{ $kategori->links() }}
@endsection