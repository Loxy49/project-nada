{{-- Isi keterangan satu catatan log (alasan + rincian perubahan). Dipakai di Log Aktivitas dan detail nota. --}}
@if($log->alasan)<div>Alasan: {{ $log->alasan }}</div>@endif
@if($log->aksi === 'ubah')
    @foreach($log->perubahan() as $baris)
        <div style="color:#475569;">{{ $baris }}</div>
    @endforeach
@endif
