@extends('layouts.app')
@section('title', 'Pengguna')

@section('content')
    <a href="{{ route('pengguna.create') }}" class="btn" style="margin-bottom:12px; display:inline-block;">+ Tambah pengguna</a>

    <div class="card">
        <table>
            <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Departemen</th><th>Status</th><th>Aksi</th></tr>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->role === 'admin' ? 'Admin' : 'Karyawan' }}</td>
                    <td>{{ $u->departemen ?: '-' }}</td>
                    <td>
                        @if($u->aktif)
                            <span style="font-size:12px; font-weight:600; color:#3f6149;">Aktif</span>
                        @else
                            <span style="font-size:12px; font-weight:600; color:#991b1b;">Nonaktif</span>
                        @endif
                    </td>
                    <td><a href="{{ route('pengguna.edit', $u) }}" class="btn btn-outline" style="padding:5px 12px; font-size:12px;">Edit</a></td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection
