@extends('layouts.app')
@section('title', 'Edit Pengguna')

@section('content')
    <div class="card" style="max-width:440px;">
        <form method="POST" action="{{ route('pengguna.update', $user) }}">
            @csrf @method('PUT')
            <div class="field">
                <label>Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Peran</label>
                <select name="role" required>
                    <option value="karyawan" @selected(old('role', $user->role) === 'karyawan')>Karyawan</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin (accounting)</option>
                </select>
                @error('role') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Departemen (opsional)</label>
                <input type="text" name="departemen" value="{{ old('departemen', $user->departemen) }}" maxlength="100">
                @error('departemen') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="hidden" name="aktif" value="0">
                    <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $user->aktif))>
                    Akun aktif (bisa login)
                </label>
                <small style="color:#64748b;">Akun tidak dihapus supaya nota yang pernah dibuatnya tetap utuh. Hapus centang ini jika yang bersangkutan sudah tidak bekerja.</small>
            </div>
            <div class="field">
                <label>Atur ulang password (kosongkan jika tidak diganti)</label>
                <input type="password" name="password" minlength="8" autocomplete="new-password">
                @error('password') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label>Ulangi password baru</label>
                <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('pengguna.index') }}" class="btn btn-outline" style="margin-left:6px;">Batal</a>
        </form>
    </div>
@endsection
