{{-- Satu lampiran (file/tautan). Param: $att, $task (jika $manage), $manage (bool: tampilkan menu ⋯ ubah/hapus), $destroyRoute (opsional; default rute peserta) --}}
@php($isLink = $att->isLink())
<div class="wl-work-item">
    <i class="mdi {{ $att->icon() }} wl-work-ic"></i>
    <div class="wl-work-name">
        @if ($isLink)
            <a href="{{ $att->url }}" target="_blank" rel="noopener noreferrer" title="{{ $att->url }}">{{ $att->name }}</a>
        @else
            <a href="{{ route('work-logs.file.attachment', $att) }}" target="_blank" title="{{ $att->name }}">{{ $att->name }}</a>
        @endif
    </div>
    @include('includes.work-uploader', ['by' => $att->uploader ?: optional($att->assignee)->user, 'at' => $att->created_at])

    @if (!empty($manage))
        <div class="wl-menu-wrap">
            <button type="button" class="wl-dots" data-wl-menu aria-label="Opsi lampiran"><i class="mdi mdi-dots-horizontal"></i></button>
            <div class="wl-menu">
                @if ($isLink)
                    <a href="{{ $att->url }}" target="_blank" rel="noopener noreferrer"><i class="mdi mdi-open-in-new"></i> Buka tautan</a>
                @else
                    <a href="{{ route('work-logs.file.attachment', $att) }}" target="_blank"><i class="mdi mdi-eye-outline"></i> Lihat</a>
                    <a href="{{ route('work-logs.file.attachment', ['attachment' => $att, 'download' => 1]) }}"><i class="mdi mdi-download-outline"></i> Unduh</a>
                @endif
                <hr>
                <form method="POST" action="{{ route($destroyRoute ?? 'user.work-logs.attachments.destroy', ['task' => $task, 'attachment' => $att]) }}"
                    data-confirm="Hapus lampiran ini?" data-confirm-ok="Ya, hapus">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="is-danger"><i class="mdi mdi-trash-can-outline"></i> Hapus</button>
                </form>
            </div>
        </div>
    @elseif ($isLink)
        <a class="wl-work-dl" href="{{ $att->url }}" target="_blank" rel="noopener noreferrer">Buka</a>
    @else
        <a class="wl-work-dl" href="{{ route('work-logs.file.attachment', ['attachment' => $att, 'download' => 1]) }}">Unduh</a>
    @endif
</div>
