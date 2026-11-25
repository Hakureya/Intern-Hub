@extends('layouts.viewer')

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=19" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="wl" style="margin-top: 20px;">
        <div class="wl-head">
            <div>
                <h2 class="wl-title">Log Pekerjaan</h2>
                <p class="wl-sub">Daftar tugas peserta magang divisi {{ optional($viewer->division)->name ?: '-' }} per bulan (hanya lihat).</p>
            </div>
        </div>

        <form method="GET" action="{{ route('viewer.work-logs.index') }}" class="wl-filters">
            <div class="wl-f wl-f--wide">
                <label class="wl-label" for="wl-q">Cari tugas</label>
                <input type="text" id="wl-q" name="q" value="{{ $search }}" class="wl-input" placeholder="ID tugas, judul, atau deskripsi...">
            </div>
            <div class="wl-f">
                <label class="wl-label" for="wl-month">Bulan</label>
                <input type="month" id="wl-month" name="month" value="{{ $month }}" class="wl-input" onchange="this.form.submit()">
            </div>
            <div class="wl-f">
                <label class="wl-label" for="wl-user">Anak magang</label>
                <select id="wl-user" name="user_id" class="wl-select" onchange="this.form.submit()">
                    <option value="">Semua peserta</option>
                    @foreach ($participants as $p)
                        <option value="{{ $p->id }}" {{ $userId === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="wl-f" style="flex: 0 0 auto; display: flex; gap: 8px;">
                <button type="submit" class="wl-btn wl-btn--primary">Cari</button>
                <a href="{{ route('viewer.work-logs.index', ['type' => $type]) }}" class="wl-btn">Reset</a>
            </div>
        </form>

        <?php $tq = array_filter(['month' => $month, 'q' => $search, 'user_id' => $userId]); ?>
        <div class="wl-typetabs">
            <a class="wl-typetab {{ $type === 'project' ? 'is-active' : '' }}" href="{{ route('viewer.work-logs.index', $tq + ['type' => 'project']) }}">
                <i class="mdi mdi-briefcase-outline"></i> Tugas Proyek <small>{{ $typeCounts['project'] }}</small></a>
            <a class="wl-typetab {{ $type === 'daily' ? 'is-active' : '' }}" href="{{ route('viewer.work-logs.index', $tq + ['type' => 'daily']) }}">
                <i class="mdi mdi-calendar-check-outline"></i> Tugas Harian <small>{{ $typeCounts['daily'] }}</small></a>
        </div>

        <div class="wl-stats">
            <?php $sq = $tq + ['type' => $type]; ?>
            <a class="wl-stat {{ $status === '' ? 'is-active' : '' }}" href="{{ route('viewer.work-logs.index', $sq) }}">
                <b>{{ $stats['semua'] }}</b><span>Semua tugas</span></a>
            <a class="wl-stat {{ $status === 'sudah' ? 'is-active' : '' }}" href="{{ route('viewer.work-logs.index', $sq + ['status' => 'sudah']) }}">
                <b>{{ $stats['sudah'] }}</b><span>Sudah selesai</span></a>
            <a class="wl-stat {{ $status === 'menunggu' ? 'is-active' : '' }}" href="{{ route('viewer.work-logs.index', $sq + ['status' => 'menunggu']) }}">
                <b>{{ $stats['menunggu'] }}</b><span>Menunggu persetujuan</span></a>
            <a class="wl-stat {{ $status === 'belum' ? 'is-active' : '' }}" href="{{ route('viewer.work-logs.index', $sq + ['status' => 'belum']) }}">
                <b>{{ $stats['belum'] }}</b><span>Belum dikerjakan</span></a>
        </div>

        <div class="wl-list">
            @forelse ($groups as $date => $groupRows)
                <?php $day = \Carbon\Carbon::parse($date)->locale('id'); ?>
                <div class="wl-day-head">
                    <b>{{ $day->translatedFormat($day->isCurrentYear() ? 'd M' : 'd M Y') }}</b>
                    <span>{{ $day->isToday() ? 'Hari ini' : ($day->isYesterday() ? 'Kemarin' : ucfirst($day->diffForHumans())) }}</span>
                </div>
                @foreach ($groupRows as $row)
                    <?php
                        $task = $row['task'];
                        $names = $row['assignees']->map(function ($a) { return optional($a->user)->name; })->filter()->values();
                    ?>
                    <div class="wl-row">
                        <a class="wl-row-link" href="{{ route('viewer.work-logs.show', $task) }}" aria-label="Buka {{ $task->title }}"></a>
                        <div class="wl-row-main">
                            <h4 class="wl-row-title"><span class="wl-code">{{ $task->code }}</span> {{ $task->title }}</h4>
                            <div class="wl-row-meta">
                                @if ($task->isDaily())
                                    @if ($task->categoryLabel()) <span><i class="mdi mdi-tag-outline"></i> {{ $task->categoryLabel() }}</span> @endif
                                    @if ($task->quantity !== null) <span><i class="mdi mdi-counter"></i> {{ number_format($task->quantity, 0, ',', '.') }} {{ $task->quantity_unit }}</span> @endif
                                    @if ($task->workStatusLabel()) <span class="wl-badge wl-badge--{{ $task->work_status }}">{{ $task->workStatusLabel() }}</span> @endif
                                @endif
                                <span><i class="mdi mdi-calendar-clock"></i> {{ $task->dateRangeText() }}</span>
                                <span><i class="mdi mdi-account-outline"></i>
                                    {{ $names->take(2)->implode(', ') }}@if ($names->count() > 2) +{{ $names->count() - 2 }} lainnya @endif
                                </span>
                            </div>
                            @include('includes.work-target-bar', ['task' => $task])
                        </div>
                        <div class="wl-row-status">
                            @if ($row['counts']['menunggu'])
                                <span class="wl-badge wl-badge--menunggu">{{ $row['counts']['menunggu'] }} menunggu persetujuan</span>
                            @endif
                            @if ($row['counts']['sudah'])
                                <span class="wl-badge wl-badge--sudah">{{ $row['counts']['sudah'] }} sudah selesai</span>
                            @endif
                            @if ($row['counts']['belum'])
                                <span class="wl-badge wl-badge--belum">{{ $row['counts']['belum'] }} belum dikerjakan</span>
                            @endif
                            @if ($row['counts']['batal'])
                                <span class="wl-badge wl-badge--batal">{{ $row['counts']['batal'] }} batal</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @empty
                <div class="wl-empty">
                    <i class="mdi mdi-clipboard-text-outline" style="font-size: 34px;"></i>
                    <p class="mb-0 mt-2">{{ $status !== '' || $search !== '' || $userId ? 'Tidak ada tugas yang cocok dengan filter.' : ($type === 'daily' ? 'Belum ada tugas harian pada bulan ini.' : 'Belum ada tugas proyek pada bulan ini.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=12"></script>
@endsection
