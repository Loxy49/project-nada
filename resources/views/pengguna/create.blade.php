@extends('layouts.app')
@section('title', 'Tambah Pengguna')

@section('content')
    <div class="card" style="max-width:440px;">
        <form method="POST" action="{{ route('pengguna.store') }}">
            @csrf
            <div class="field">
                <label>Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
                @error('name') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
                @error('email') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Peran</label>
                <select name="role" required>
                    <option value="karyawan" @selected(old('role', 'karyawan') === 'karyawan')>Karyawan</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin (accounting)</option>
                </select>
                @error('role') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Departemen (opsional)</label>
                <input type="text" name="departemen" value="{{ old('departemen') }}" maxlength="100">
                @error('departemen') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Password sementara</label>
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
                @error('password') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Ulangi password</label>
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn">Simpan</button>
        </form>
    </div>
@endsection
