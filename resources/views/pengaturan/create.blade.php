@extends('layouts.app')
@section('title', 'Tambah Template Nota')

@section('content')
    <div class="card" style="max-width:480px; margin-left:auto; margin-right:auto;">
        <form method="POST" action="{{ route('pengaturan.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="field">
                <label>Nama template</label>
                <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Contoh: Formal Kantor, Struk Toko A" required>
                @error('nama') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <div class="field" style="display:flex; align-items:center; gap:14px;">
                <div style="width:56px; height:56px; border-radius:10px; background:#f7f6f2; border:1px solid #e5e4e2; display:flex; align-items:center; justify-content:center; color:#5a7a62;"><x-ikon nama="nota" :ukuran="24" /></div>
                <div style="flex:1;">
                    <label>Logo toko/kantor</label>
                    <input type="file" name="logo" accept="image/*" id="input-logo">
                    @error('logo') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="field">
                <label>Nama toko / kantor</label>
                <input type="text" name="nama_toko" id="input-nama-toko" value="{{ old('nama_toko') }}" required>
            </div>
            <div class="field">
                <label>Alamat</label>
                <input type="text" name="alamat" id="input-alamat" value="{{ old('alamat') }}">
            </div>
            <div class="field">
                <label>No. CS</label>
                <input type="text" name="no_cs" id="input-no-cs" value="{{ old('no_cs') }}">
            </div>
            <div class="field">
                <label>Teks footer nota</label>
                <textarea name="teks_footer" id="input-footer" rows="2">{{ old('teks_footer') }}</textarea>
            </div>

            <div class="field">
                <label>Gaya cetak default</label>
                <select name="gaya_default" id="input-gaya">
                    <option value="struk" @selected(old('gaya_default', 'struk') === 'struk')>Struk</option>
                    <option value="formal" @selected(old('gaya_default') === 'formal')>Formal</option>
                </select>
                @error('gaya_default') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label>Orientasi kertas</label>
                <select name="orientasi" id="input-orientasi">
                    <option value="potret" @selected(old('orientasi', 'potret') === 'potret')>Portrait</option>
                    <option value="lanskap" @selected(old('orientasi') === 'lanskap')>Landscape</option>
                </select>
                <small style="color:#9ca3af;">Berlaku untuk gaya Formal maupun Struk.</small>
                @error('orientasi') <small style="color:#B91C1C;">{{ $message }}</small> @enderror
            </div>

            <div class="field" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="is_default" name="is_default" value="1" style="width:auto;" @checked(old('is_default'))>
                <label for="is_default" style="margin:0;">Jadikan template default</label>
            </div>

            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="btn btn-outline" id="btn-preview" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    Preview
                </button>
                <button type="submit" class="btn">Simpan template</button>
            </div>
        </form>
    </div>

    @include('pengaturan._preview-script', ['existingLogoUrl' => null])
@endsection
