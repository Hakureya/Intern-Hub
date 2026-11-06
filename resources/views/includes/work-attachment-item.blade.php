{{-- Satu lampiran (file/tautan). Param: $att, $task (jika $manage), $manage (bool: tampilkan menu ⋯ ubah/hapus) --}}
@php($isLink = $att->isLink())
<div class="wl-work-item">
    <i class="mdi {{ $att->icon() }} wl-work-ic"></i>
    <div class="wl-work-name">
        @if ($isLink)
            <a href="{{ $att->url }}" target="_blank" rel="noopener noreferrer">{{ $att->name }}</a>
            <small>{{ \Illuminate\Support\Str::limit($att->url, 60) }}</small>
        @else
            <a href="{{ route('work-logs.file.attachment', $att) }}" target="_blank">{{ $att->name }}</a>
        @endif
    </div>

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
                <form method="POST" action="{{ route('user.work-logs.attachments.destroy', ['task' => $task, 'attachment' => $att]) }}"
                    onsubmit="return confirm('Hapus lampiran ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="is-danger"><i class="mdi mdi-trash-can-outline"></i> Hapus</button>
                </form>
            </div>
        </div>
    @elseif (!$isLink)
        <a class="wl-work-dl" href="{{ route('work-logs.file.attachment', ['attachment' => $att, 'download' => 1]) }}">Unduh</a>
    @endif
</div>
