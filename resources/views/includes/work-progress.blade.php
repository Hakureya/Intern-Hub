{{--
    Turunan tugas / catatan progres pada satu tugas. Param: $task.
    - Tugas harian dengan turunan : tiap turunan = tanggal pengerjaan + jumlah output (+ catatan opsional); total & bar target di atasnya.
    - Tugas proyek                 : catatan progres bertahap (garis waktu).
    - Tugas harian pencatatan      : tidak ada bagian ini.
--}}
@if (! $task->isDaily() || $task->has_children)
<?php
    $pgMe = auth()->user();
    $pgChildren = (bool) $task->has_children;
    $pgEntries = $task->progress()->with('user')->get();
    $pgCanAdd = $task->canAddProgressBy($pgMe);
    $pgHasTarget = $task->hasTarget();
    $pgTotal = (int) $pgEntries->sum('quantity');
    $pgPct = $pgHasTarget ? (int) min(100, floor($pgTotal * 100 / $task->target_quantity)) : 0;
    $pgPerUser = $pgEntries->filter(function ($e) { return $e->quantity !== null; })->groupBy('user_id');
?>
<h4 class="wl-sub-title">{{ $pgChildren ? 'Turunan tugas' : 'Catatan progres' }}</h4>

@if ($pgChildren)
    <div class="wl-target-box">
        <div class="wl-target-top">
            <b>{{ number_format($pgTotal, 0, ',', '.') }}</b>
            {{ $task->quantity_unit }}
            @if ($pgHasTarget) dari {{ number_format($task->target_quantity, 0, ',', '.') }} @endif
            <span class="wl-target-pct">@if ($pgHasTarget) {{ $pgPct }}% @else {{ $pgEntries->count() }} turunan @endif</span>
        </div>
        @if ($pgHasTarget)
            <div class="wl-bar {{ $pgPct >= 100 ? 'is-done' : ($task->isPastDeadline() ? 'is-late' : '') }}"><i style="width: {{ $pgPct }}%"></i></div>
        @endif
        <div class="wl-hint" style="margin-top: 6px;">
            @if ($pgHasTarget)
                @if ($pgPct >= 100) Target tercapai. @else Sisa {{ number_format(max(0, $task->target_quantity - $pgTotal), 0, ',', '.') }} {{ $task->quantity_unit }}. @endif
            @else
                Total output dari seluruh turunan.
            @endif
            @if ($task->deadline)
                Tenggat {{ $task->deadline->locale('id')->translatedFormat('d M Y') }}@if ($pgPct < 100 && $task->isPastDeadline()) (sudah lewat)@endif.
            @endif
        </div>
        @if ($pgPerUser->count() > 1)
            <div class="wl-target-people">
                @foreach ($pgPerUser as $uid => $list)
                    <span><i class="mdi mdi-account-outline"></i> {{ optional($list->first()->user)->name ?: '-' }}: <b>{{ number_format($list->sum('quantity'), 0, ',', '.') }}</b> {{ $task->quantity_unit }}</span>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if ($pgEntries->isEmpty())
    <div class="wl-hint" style="margin-bottom: 10px;">{{ $pgChildren ? 'Belum ada turunan. Tambahkan pengerjaan hari ini di bawah.' : 'Belum ada catatan progres.' }}</div>
@else
    <div class="wl-tl">
        @foreach ($pgEntries as $e)
            <div class="wl-tl-item">
                <span class="wl-tl-dot"></span>
                <div class="wl-tl-body">
                    <div class="wl-tl-head">
                        <b>{{ $e->entry_date->locale('id')->translatedFormat('d M Y') }}</b>
                        <span class="wl-tl-by">{{ optional($e->user)->name ?: '-' }}</span>
                        @if ($e->quantity !== null)
                            <span class="wl-badge wl-badge--info">{{ number_format($e->quantity, 0, ',', '.') }} {{ $task->quantity_unit }}</span>
                        @endif
                        @if ($task->canRemoveProgressBy($pgMe, $e))
                            <form method="POST" action="{{ route('work-logs.progress.destroy', [$task, $e]) }}" class="wl-tl-del"
                                onsubmit="return confirm('Hapus {{ $pgChildren ? 'turunan' : 'catatan' }} ini?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="wl-tl-del-btn" title="Hapus"><i class="mdi mdi-close"></i></button>
                            </form>
                        @endif
                    </div>
                    @if ($e->note)
                        <div class="wl-tl-note">{{ $e->note }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@if ($pgCanAdd)
    <form method="POST" action="{{ route('work-logs.progress.store', $task) }}" class="wl-tl-form">
        @csrf
        <div class="wl-tl-form-row">
            <div class="wl-field" style="margin: 0;">
                <label class="wl-label" for="pg-date-{{ $task->id }}">{{ $pgChildren ? 'Tanggal pengerjaan' : 'Tanggal' }}</label>
                <input type="date" id="pg-date-{{ $task->id }}" name="entry_date" class="wl-input" required
                    max="{{ now()->toDateString() }}" value="{{ old('entry_date', now()->toDateString()) }}">
            </div>
            @if ($pgChildren)
                <div class="wl-field" style="margin: 0;">
                    <label class="wl-label" for="pg-qty-{{ $task->id }}">Jumlah output ({{ $task->quantity_unit }})</label>
                    <input type="number" id="pg-qty-{{ $task->id }}" name="quantity" class="wl-input" min="1" max="1000000" step="1" required
                        placeholder="Contoh: 120" value="{{ old('quantity') }}">
                </div>
            @endif
        </div>
        <div class="wl-field" style="margin: 10px 0 8px;">
            <label class="wl-label" for="pg-note-{{ $task->id }}">Catatan {{ $pgChildren ? '(opsional)' : '' }}</label>
            <textarea id="pg-note-{{ $task->id }}" name="note" class="wl-textarea" maxlength="1000" rows="2"
                placeholder="{{ $pgChildren ? 'Contoh: Bantex 2019 urutan 1–12.' : 'Contoh: Dokumen PIB lengkap, menunggu Inward Manifest.' }}">{{ old('note') }}</textarea>
        </div>
        @foreach (['entry_date', 'quantity', 'note'] as $k)
            @error($k) <div class="wl-error">{{ $message }}</div> @enderror
        @endforeach
        <button type="submit" class="wl-btn wl-btn--primary"><i class="mdi mdi-plus"></i> {{ $pgChildren ? 'Tambah turunan' : 'Tambah catatan' }}</button>
    </form>
@endif
@endif
