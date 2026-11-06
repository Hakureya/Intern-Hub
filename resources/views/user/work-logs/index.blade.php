@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-log.css') }}?v=6" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@include('layouts.user-navbar', ['active' => 'worklog'])

<div class="home-wrap">
    <div class="home-shell wl">
        <div class="wl-head">
            <div>
                <h2 class="wl-title">Log Pekerjaan</h2>
                <p class="wl-sub">Tugas dari mentor dan pekerjaan yang kamu catat sendiri.</p>
            </div>
            <div class="wl-head-actions">
                <button type="button" class="wl-btn" data-wl-open="#wl-requests">
                    <i class="mdi mdi-account-clock-outline"></i> Daftar permintaan
                    @if ($pendingCount) <span class="wl-count">{{ $pendingCount }}</span> @endif
                </button>
                <a href="{{ route('user.work-logs.create') }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-plus"></i> Tambah tugas</a>
            </div>
        </div>

        <form method="GET" action="{{ route('user.work-logs.index') }}" class="wl-filters" id="wl-month-form">
            <div class="wl-f" style="max-width: 220px;">
                <label class="wl-label" for="wl-month">Bulan</label>
                <input type="month" id="wl-month" name="month" value="{{ $month }}" class="wl-input" onchange="this.form.submit()">
            </div>
            <input type="hidden" name="status" value="{{ $status }}">
        </form>

        <div class="wl-stats">
            @php($q = ['month' => $month])
            <a class="wl-stat {{ $status === '' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q) }}">
                <b>{{ array_sum($stats) }}</b><span>Semua tugas</span></a>
            <a class="wl-stat {{ $status === 'sudah' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q + ['status' => 'sudah']) }}">
                <b>{{ $stats['sudah'] }}</b><span>Sudah</span></a>
            <a class="wl-stat {{ $status === 'menunggu' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q + ['status' => 'menunggu']) }}">
                <b>{{ $stats['menunggu'] }}</b><span>Menunggu persetujuan</span></a>
            <a class="wl-stat {{ $status === 'belum' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q + ['status' => 'belum']) }}">
                <b>{{ $stats['belum'] }}</b><span>Belum</span></a>
        </div>

        <div class="wl-list">
            @forelse ($rows as $row)
                @php($task = $row['task'])
                @php($mine = $row['mine'])
                <div class="wl-row">
                    <a class="wl-row-link" href="{{ route('user.work-logs.show', $task) }}" aria-label="Buka {{ $task->title }}"></a>
                    <div class="wl-row-main">
                        <h4 class="wl-row-title">{{ $task->title }}</h4>
                        <div class="wl-row-meta">
                            <span><i class="mdi mdi-calendar-clock"></i>
                                {{ $task->deadline ? 'Tenggat ' . $task->deadline->locale('id')->translatedFormat('d M Y') : 'Tanpa tenggat' }}
                            </span>
                            @if ($task->deadline && !$task->isPastDeadline() && optional($mine)->status !== 'sudah')
                                @include('includes.work-countdown', ['task' => $task])
                            @endif
                            <span><i class="mdi mdi-account-edit-outline"></i> Dibuat oleh: {{ $task->is_self ? 'Saya' : (optional($task->creator)->name ?: '-') }}</span>
                            <span class="wl-badge {{ $task->is_self ? 'wl-badge--info' : 'wl-badge--muted' }}">
                                {{ $task->is_self ? 'Dicatat sendiri' : 'Dari mentor' }}
                            </span>
                            @if ($task->require_attachment && !$task->is_self)
                                <span><i class="mdi mdi-paperclip"></i> Wajib lampiran</span>
                            @endif
                        </div>
                    </div>
                    <div class="wl-row-status">
                        @if ($mine)
                            <span class="wl-badge wl-badge--{{ $mine->status }}">{{ $mine->statusLabel() }}</span>
                        @endif
                    </div>
                    @include('includes.work-people', ['people' => $row['people']])
                </div>
            @empty
                <div class="wl-empty">
                    <i class="mdi mdi-clipboard-text-outline" style="font-size: 34px;"></i>
                    <p class="mb-0 mt-2">Belum ada tugas pada bulan ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Popup daftar permintaan kerja bersama --}}
<div class="wl-modal wl" id="wl-requests">
    <div class="wl-modal-box">
        <div class="wl-modal-head">
            <h5>Daftar permintaan</h5>
            <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
        </div>
        <div class="wl-tabs">
            <button type="button" class="wl-tab is-active" data-wl-tab="#wl-pane-in">Diterima ({{ $received->count() }})</button>
            <button type="button" class="wl-tab" data-wl-tab="#wl-pane-out">Dikirim ({{ $sent->count() }})</button>
        </div>
        <div class="wl-modal-body">
            <div class="wl-tabpane is-active" id="wl-pane-in">
                @forelse ($received as $r)
                    <div class="wl-req">
                        <div>
                            <div class="wl-req-title">{{ optional($r->task)->title }}</div>
                            <div class="wl-req-sub">Dari {{ optional($r->from)->name }} &middot; {{ optional(optional($r->from)->division)->name ?: '-' }}
                                &middot; mengerjakan bersama?</div>
                        </div>
                        @if ($r->status === 'pending')
                            <form method="POST" action="{{ route('user.work-logs.requests.respond', $r) }}" class="wl-req-actions">
                                @csrf
                                <button name="action" value="accept" class="wl-btn wl-btn--ok wl-btn--sm">Terima</button>
                                <button name="action" value="decline" class="wl-btn wl-btn--danger wl-btn--sm">Tolak</button>
                            </form>
                        @else
                            <span class="wl-badge {{ $r->status === 'accepted' ? 'wl-badge--sudah' : 'wl-badge--belum' }}">{{ \App\Models\WorkTaskPartnerRequest::LABELS[$r->status] }}</span>
                        @endif
                    </div>
                @empty
                    <div class="wl-empty">Belum ada permintaan masuk.</div>
                @endforelse
            </div>
            <div class="wl-tabpane" id="wl-pane-out">
                @forelse ($sent as $r)
                    <div class="wl-req">
                        <div>
                            <div class="wl-req-title">{{ optional($r->task)->title }}</div>
                            <div class="wl-req-sub">Ke {{ optional($r->to)->name }} &middot; {{ optional(optional($r->to)->division)->name ?: '-' }}</div>
                        </div>
                        <span class="wl-badge {{ $r->status === 'accepted' ? 'wl-badge--sudah' : ($r->status === 'declined' ? 'wl-badge--belum' : 'wl-badge--menunggu') }}">
                            {{ \App\Models\WorkTaskPartnerRequest::LABELS[$r->status] }}
                        </span>
                    </div>
                @empty
                    <div class="wl-empty">Belum ada permintaan terkirim.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=4"></script>
@endsection
