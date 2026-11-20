{{-- Satu lampiran referensi materi (file/tautan), dari mentor atau peserta. Param: $ref; opsional $canRemove (bool) + $task untuk menu hapus oleh pengunggahnya; $destroyRoute (default rute peserta). --}}
<div class="wl-work-item">
    <i class="mdi {{ $ref->icon() }} wl-work-ic"></i>
    <div class="wl-work-name">
        @if ($ref->isLink())
            <a href="{{ $ref->url }}" target="_blank" rel="noopener noreferrer" title="{{ $ref->url }}">{{ $ref->name }}</a>
        @else
            <a href="{{ route('work-logs.file.reference', $ref) }}" target="_blank" title="{{ $ref->name }}">{{ $ref->name }}</a>
        @endif
    </div>
    @include('includes.work-uploader', ['by' => $ref->uploader ?: optional($ref->task)->creator, 'at' => $ref->created_at])
    @if (!empty($canRemove))
        <div class="wl-menu-wrap">
            <button type="button" class="wl-dots" data-wl-menu aria-label="Opsi referensi"><i class="mdi mdi-dots-horizontal"></i></button>
            <div class="wl-menu">
                @if ($ref->isLink())
                    <a href="{{ $ref->url }}" target="_blank" rel="noopener noreferrer"><i class="mdi mdi-open-in-new"></i> Buka tautan</a>
                @else
                    <a href="{{ route('work-logs.file.reference', $ref) }}" target="_blank"><i class="mdi mdi-eye-outline"></i> Lihat</a>
                    <a href="{{ route('work-logs.file.reference', ['reference' => $ref, 'download' => 1]) }}"><i class="mdi mdi-download-outline"></i> Unduh</a>
                @endif
                <hr>
                <?php $refOwn = (int) $ref->uploaded_by === (int) auth()->id(); ?>
                <form method="POST" action="{{ route($destroyRoute ?? 'user.work-logs.references.destroy', ['task' => $task, 'reference' => $ref]) }}"
                    data-confirm="{{ $refOwn ? 'Hapus referensi ini?' : 'Hapus referensi "' . $ref->name . '" yang diunggah ' . (optional($ref->uploader)->name ?: 'pengguna lain') . '?' }}"
                    data-confirm-ok="Ya, hapus" data-confirm-tone="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="is-danger"><i class="mdi mdi-trash-can-outline"></i> Hapus</button>
                </form>
            </div>
        </div>
    @elseif ($ref->isLink())
        <a class="wl-work-dl" href="{{ $ref->url }}" target="_blank" rel="noopener noreferrer">Buka</a>
    @else
        <a class="wl-work-dl" href="{{ route('work-logs.file.reference', ['reference' => $ref, 'download' => 1]) }}">Unduh</a>
    @endif
</div>
