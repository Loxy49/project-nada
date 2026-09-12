@extends('layouts.app')
@section('title', 'Edit Kategori')

@section('content')
    <div class="card" style="max-width:400px;">
        <form method="POST" action="{{ route('kategori.update', $kategori) }}">
            @csrf @method('PUT')
            <div class="field">
                <label>Nama kategori</label>
                <input type="text" name="nama" value="{{ old('nama', $kategori->nama) }}" required>
                @error('nama') <small style="color:#DC2626;">{{ $message }}</small> @enderror
            </div>
            <button type="submit" class="btn">Simpan perubahan</button>
        </form>
    </div>
@endsection
