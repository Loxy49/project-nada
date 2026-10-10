@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
    <div class="card" style="max-width:480px;">
        <h3 style="margin-top:0;">Informasi profil</h3>
        <p style="font-size:13px; color:#6b7280;">Perbarui nama dan email akun Anda.</p>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <div class="field">
                <label>Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                @error('name') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn">Simpan</button>
            @if (session('status') === 'profile-updated')
                <span style="font-size:13px; color:#5a7a62; margin-left:10px;">Tersimpan.</span>
            @endif
        </form>
    </div>

    <div class="card" style="max-width:480px;">
        <h3 style="margin-top:0;">Ubah password</h3>
        <p style="font-size:13px; color:#6b7280;">Gunakan password yang panjang dan acak agar akun tetap aman.</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="field">
                <label>Password saat ini</label>
                <input type="password" name="current_password" autocomplete="current-password">
                @error('current_password', 'updatePassword') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label>Password baru</label>
                <input type="password" name="password" autocomplete="new-password">
                @error('password', 'updatePassword') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label>Konfirmasi password baru</label>
                <input type="password" name="password_confirmation" autocomplete="new-password">
                @error('password_confirmation', 'updatePassword') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn">Simpan</button>
            @if (session('status') === 'password-updated')
                <span style="font-size:13px; color:#5a7a62; margin-left:10px;">Tersimpan.</span>
            @endif
        </form>
    </div>

    <div class="card" style="max-width:480px;">
        <h3 style="margin-top:0;">Akun</h3>
        <p style="font-size:13px; color:#6b7280; margin:0;">Akun tidak bisa dihapus sendiri supaya riwayat nota tetap utuh. Jika Anda sudah tidak bekerja, minta admin untuk menonaktifkan akun Anda.</p>
    </div>
@endsection