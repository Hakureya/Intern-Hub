{{-- Ringkasan tugas dengan turunan di daftar: bar progres bila ada target, selain itu total output. Param: $task (dengan progress_sum_quantity bila ada). --}}
@if ($task->has_children)
    @if ($task->hasTarget())
        <?php $pct = $task->progressPercent(); $late = $pct < 100 && $task->isPastDeadline(); ?>
        <div class="wl-bar-wrap">
            <div class="wl-bar {{ $pct >= 100 ? 'is-done' : ($late ? 'is-late' : '') }}"><i style="width: {{ $pct }}%"></i></div>
            <span class="wl-bar-text">
                {{ number_format($task->progressTotal(), 0, ',', '.') }} / {{ number_format($task->target_quantity, 0, ',', '.') }} {{ $task->quantity_unit }} &middot; {{ $pct }}%
                @if ($late) <b class="wl-bar-late">Lewat tenggat</b> @endif
            </span>
        </div>
    @else
        <span class="wl-bar-text">Total {{ number_format($task->progressTotal(), 0, ',', '.') }} {{ $task->quantity_unit }} dari turunan tugas</span>
    @endif
@endif
