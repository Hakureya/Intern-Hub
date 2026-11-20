{{-- Keterangan "Diupload oleh ..." di sisi kanan baris lampiran. Param: $by (User|null), $at (Carbon|null). "-" bila pengunggah tidak diketahui. --}}
<span class="wl-work-by">Diupload oleh
    <b>{{ empty($by) ? '-' : ((int) $by->id === (int) auth()->id() ? (auth()->user()->hasAnyRole(['admin', 'mentor']) ? 'Anda' : 'kamu') : $by->name) }}</b>@if (!empty($at)) &bull; {{ $at->locale('id')->translatedFormat('d M Y') }}@endif
</span>
