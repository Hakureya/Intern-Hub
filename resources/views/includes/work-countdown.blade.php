{{-- Badge hitung mundur tenggat (diperbarui otomatis oleh work-log.js). Param: $task --}}
@php($cd = $task->deadlineCountdown())
@if ($cd)
    <span class="wl-badge wl-countdown {{ $cd['urgent'] ? 'wl-badge--late' : 'wl-badge--info' }}"
        data-wl-end="{{ $task->deadlineEnd()->toIso8601String() }}"><i class="mdi mdi-timer-sand"></i> <span>{{ $cd['text'] }}</span></span>
@endif
