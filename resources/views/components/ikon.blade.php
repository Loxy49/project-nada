{{-- Ikon garis (gaya sama dengan ikon sidebar), pengganti emoji. Pakai: <x-ikon nama="cetak" /> atau <x-ikon nama="kunci" :ukuran="14" />.
     Warna mengikuti teks di sekitarnya (currentColor). --}}
@props(['nama', 'ukuran' => 16])

<svg {{ $attributes->merge(['class' => 'ikon']) }} width="{{ $ukuran }}" height="{{ $ukuran }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($nama)
        @case('cetak')
            <path d="M6 9V3h12v6"/><rect x="6" y="14" width="12" height="7" rx="1"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            @break
        @case('foto')
            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
            @break
        @case('kunci')
            <rect x="5" y="11" width="14" height="9.5" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>
            @break
        @case('nota')
            <path d="M6 2.5h9l2.5 2.5v16.5l-3-1.6-3 1.6-3-1.6-3 1.6V2.5z"/><path d="M8.3 7.5h7.2M8.3 11.5h7.2M8.3 15.5H13"/>
            @break
        @case('lihat')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('edit')
            <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
            @break
        @case('hapus')
            <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
            @break
        @case('tutup')
            <path d="M18 6 6 18M6 6l12 12"/>
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16"/>
            @break
    @endswitch
</svg>
