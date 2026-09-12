@extends('layouts.app')
@section('title', 'Tambah Kategori')

@section('content')
    <div class="card" style="max-width:400px;">
        <form method="POST" action="{{ route('kategori.store') }}">
            @csrf
            <div class="field">
                <label>Nama kategori</label>
                <input type="text" name="nama" required>
                @error('nama') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <button type="submit" class="btn">Simpan</button>
        </form>
    </div>
@endsection
