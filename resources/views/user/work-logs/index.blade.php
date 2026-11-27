@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-log.css') }}?v=21" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@include('layouts.user-navbar', ['active' => 'worklog'])

<div class="home-wrap">
    <div class="home-shell wl">
        <div class="wl-head wl-head--batik">
            <div>
                <h2 class="wl-title">Log Pekerjaan</h2>
                <p class="wl-sub">Tugas dari mentor dan pekerjaan yang kamu catat sendiri.</p>
            </div>
            <div class="wl-head-actions">
                <button type="button" class="wl-btn" data-wl-open="#wl-export"><i class="mdi mdi-file-word-outline"></i> Export dokumen</button>
                <button type="button" class="wl-btn" data-wl-open="#wl-requests">
                    <i class="mdi mdi-account-clock-outline"></i> Daftar permintaan
                    @if ($pendingCount) <span class="wl-count">{{ $pendingCount }}</span> @endif
                </button>
                <a href="{{ route('user.work-logs.create', ['type' => $type]) }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-plus"></i> {{ $type === 'daily' ? 'Tambah tugas harian' : 'Tambah tugas proyek' }}</a>
            </div>
        </div>

        <form method="GET" action="{{ route('user.work-logs.index') }}" class="wl-filters" id="wl-month-form">
            <div class="wl-f wl-f--wide">
                <label class="wl-label" for="wl-q">Cari tugas</label>
                <input type="search" id="wl-q" name="q" value="{{ $search }}" class="wl-input" placeholder="ID tugas, judul, atau deskripsi..." maxlength="100">
            </div>
            <div class="wl-f" style="max-width: 220px;">
                <label class="wl-label" for="wl-month">Bulan</label>
                <input type="month" id="wl-month" name="month" value="{{ $month }}" class="wl-input" onchange="this.form.submit()">
            </div>
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="wl-f" style="flex: 0 0 auto; display: flex; gap: 8px;">
                <button type="submit" class="wl-btn wl-btn--primary">Cari</button>
                <a href="{{ route('user.work-logs.index', ['type' => $type]) }}" class="wl-btn">Reset</a>
            </div>
        </form>

        {{-- Tab bentuk tugas --}}
        @php($tq = array_filter(['month' => $month, 'q' => $search]))
        <div class="wl-typetabs">
            <a class="wl-typetab {{ $type === 'project' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $tq + ['type' => 'project']) }}">
                <i class="mdi mdi-briefcase-outline"></i> Tugas Proyek <small>{{ $typeCounts['project'] }}</small></a>
            <a class="wl-typetab {{ $type === 'daily' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $tq + ['type' => 'daily']) }}">
                <i class="mdi mdi-calendar-check-outline"></i> Tugas Harian <small>{{ $typeCounts['daily'] }}</small></a>
        </div>

        {{-- Tab status --}}
        <div class="wl-stats">
            @php($q = array_filter(['month' => $month, 'q' => $search]) + ['type' => $type])
            <a class="wl-stat {{ $status === '' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q) }}">
                <b>{{ array_sum($stats) }}</b><span>Semua tugas</span></a>
            <a class="wl-stat {{ $status === 'sudah' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q + ['status' => 'sudah']) }}">
                <b>{{ $stats['sudah'] }}</b><span>Sudah selesai</span></a>
            <a class="wl-stat {{ $status === 'menunggu' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q + ['status' => 'menunggu']) }}">
                <b>{{ $stats['menunggu'] }}</b><span>Menunggu persetujuan</span></a>
            <a class="wl-stat {{ $status === 'belum' ? 'is-active' : '' }}" href="{{ route('user.work-logs.index', $q + ['status' => 'belum']) }}">
                <b>{{ $stats['belum'] }}</b><span>Belum dikerjakan</span></a>
        </div>

        <div class="wl-list">
            @forelse ($groups as $date => $groupRows)
                {{-- Judul tanggal per kelompok (tanggal mulai tugas), sama seperti halaman mentor. --}}
                @php($day = \Carbon\Carbon::parse($date)->locale('id'))
                <div class="wl-day-head">
                    <b>{{ $day->translatedFormat($day->isCurrentYear() ? 'd M' : 'd M Y') }}</b>
                    <span>{{ $day->isToday() ? 'Hari ini' : ($day->isYesterday() ? 'Kemarin' : ucfirst($day->diffForHumans())) }}</span>
                </div>
                @foreach ($groupRows as $row)
                @php($task = $row['task'])
                @php($mine = $row['mine'])
                <div class="wl-row">
                    <a class="wl-row-link" href="{{ route('user.work-logs.show', $task) }}" aria-label="Buka {{ $task->title }}"></a>
                    <div class="wl-row-main">
                        <h4 class="wl-row-title"><span class="wl-code">{{ $task->code }}</span> {{ $task->title }}</h4>
                        <div class="wl-row-meta">
                            @if ($task->isDaily())
                                @if ($task->categoryLabel()) <span><i class="mdi mdi-tag-outline"></i> {{ $task->categoryLabel() }}</span> @endif
                                @if ($task->quantity !== null) <span><i class="mdi mdi-counter"></i> {{ number_format($task->quantity, 0, ',', '.') }} {{ $task->quantity_unit }}</span> @endif
                                @if ($task->workStatusLabel()) <span class="wl-badge wl-badge--{{ $task->work_status }}">{{ $task->workStatusLabel() }}</span> @endif
                            @endif
                            <span><i class="mdi mdi-calendar-clock"></i>
                                {{ $task->dateRangeText() }}
                            </span>
                            @if ($mine && $mine->submitted_at)
                                <span><i class="mdi mdi-send-check-outline"></i> Diajukan {{ $mine->submitted_at->locale('id')->translatedFormat('d M Y') }}</span>
                            @endif
                            @if ($task->deadline && !$task->isPastDeadline() && optional($mine)->status !== 'sudah')
                                @include('includes.work-countdown', ['task' => $task])
                            @endif
                            <span><i class="mdi mdi-account-edit-outline"></i> Dibuat oleh: {{ (int) $task->created_by === (int) auth()->id() ? 'Saya' : (optional($task->creator)->name ?: '-') }}</span>
                            <span class="wl-badge {{ $task->is_self ? 'wl-badge--info' : 'wl-badge--muted' }}">
                                {{ $task->isDaily() ? 'Harian' : ($task->is_self ? 'Dicatat sendiri' : 'Dari mentor') }}
                            </span>
                            @if ($task->require_attachment && !$task->is_self)
                                <span><i class="mdi mdi-paperclip"></i> Wajib lampiran</span>
                            @endif
                        </div>
                        @include('includes.work-target-bar', ['task' => $task])
                    </div>
                    <div class="wl-row-status">
                        @if ($mine)
                            <span class="wl-badge wl-badge--{{ $mine->status }}">{{ $mine->statusLabel() }}</span>
                        @endif
                    </div>
                    @include('includes.work-people', ['people' => $row['people']])
                </div>
                @endforeach
            @empty
                <div class="wl-empty">
                    <i class="mdi mdi-clipboard-text-outline" style="font-size: 34px;"></i>
                    <p class="mb-0 mt-2">{{ $status !== '' || $search !== '' ? 'Tidak ada tugas yang cocok dengan filter.' : ($type === 'daily' ? 'Belum ada tugas harian pada bulan ini.' : 'Belum ada tugas proyek pada bulan ini.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Popup export hasil pekerjaan ke dokumen Word --}}
@php($exportOpen = $errors->hasAny(['export', 'type', 'period', 'categories']))
@php($expType = old('type', 'both'))
@php($expPeriod = old('period', 'month'))
@php($expCats = old('categories', $exportCategories))
<div class="wl-modal wl {{ $exportOpen ? 'is-open' : '' }}" id="wl-export">
    <div class="wl-modal-box" style="width: min(560px, 100%);">
        <div class="wl-modal-head">
            <h5>Export hasil pekerjaan ke dokumen</h5>
            <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
        </div>
        <form method="GET" action="{{ route('user.work-logs.export') }}" class="wl-modal-body" id="wl-export-form">
            <div class="wl-sub" style="margin-bottom: 12px;">
                Hanya pekerjaan yang sudah selesai (disetujui mentor) yang dimasukkan. Hasilnya berupa dokumen Word (.docx) yang bisa diunduh dan dicetak.
            </div>

            @foreach (['export', 'type', 'period', 'categories'] as $k)
                @error($k) <div class="wl-error" style="margin-bottom: 8px;">{{ $message }}</div> @enderror
            @endforeach

            <div class="wl-field">
                <label class="wl-label">Jenis tugas</label>
                <div class="wl-radio-group">
                    @foreach (\App\Support\WorkLogReport::TYPES as $val => $text)
                        <label class="wl-radio"><input type="radio" name="type" value="{{ $val }}" {{ $expType === $val ? 'checked' : '' }}> {{ $text }}</label>
                    @endforeach
                </div>
            </div>

            <div class="wl-field">
                <label class="wl-label">Format tanggal</label>
                <div class="wl-radio-group">
                    @foreach (\App\Support\WorkLogReport::PERIODS as $val => $text)
                        <label class="wl-radio"><input type="radio" name="period" value="{{ $val }}" {{ $expPeriod === $val ? 'checked' : '' }}> {{ $text }}</label>
                    @endforeach
                </div>
            </div>

            <div class="wl-field" id="wl-exp-cats">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <label class="wl-label" style="margin: 0;">Kategori tugas harian yang dimasukkan</label>
                    <span>
                        <button type="button" class="wl-btn wl-btn--sm" data-exp-cats="all">Pilih semua</button>
                        <button type="button" class="wl-btn wl-btn--sm" data-exp-cats="none">Kosongkan</button>
                    </span>
                </div>
                <div style="display: grid; gap: 6px; margin-top: 8px; max-height: 220px; overflow-y: auto;">
                    @forelse ($exportCategories as $cat)
                        <label class="wl-radio" style="justify-content: flex-start;">
                            <input type="checkbox" name="categories[]" value="{{ $cat }}" {{ in_array($cat, (array) $expCats, true) ? 'checked' : '' }}>
                            {{ $cat === \App\Support\WorkLogReport::NO_CATEGORY ? 'Tanpa kategori' : $cat }}
                        </label>
                    @empty
                        <div class="wl-hint">Belum ada kategori yang pernah dikerjakan.</div>
                    @endforelse
                </div>
                <div class="wl-hint" style="margin-top: 6px;">Hanya kategori yang pernah diselesaikan</div>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="wl-btn" data-wl-close>Batal</button>
                <button type="submit" class="wl-btn wl-btn--primary"><i class="mdi mdi-download"></i> Export .docx</button>
            </div>
        </form>
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
            <button type="button" class="wl-tab" data-wl-tab="#wl-pane-history">Riwayat ({{ $history->count() }})</button>
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
                        <form method="POST" action="{{ route('user.work-logs.requests.respond', $r) }}" class="wl-req-actions">
                            @csrf
                            <button name="action" value="accept" class="wl-btn wl-btn--ok wl-btn--sm">Terima</button>
                            <button name="action" value="decline" class="wl-btn wl-btn--danger wl-btn--sm">Tolak</button>
                        </form>
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
                        <span class="wl-badge wl-badge--menunggu">{{ \App\Models\WorkTaskPartnerRequest::LABELS[$r->status] }}</span>
                    </div>
                @empty
                    <div class="wl-empty">Belum ada permintaan terkirim.</div>
                @endforelse
            </div>
            {{-- Riwayat: permintaan masuk maupun keluar yang sudah diterima, ditolak, atau dibatalkan. --}}
            <div class="wl-tabpane" id="wl-pane-history">
                @forelse ($history as $r)
                    @php($incoming = (int) $r->to_user_id === (int) auth()->id())
                    @php($other = $incoming ? $r->from : $r->to)
                    <div class="wl-req">
                        <div>
                            <div class="wl-req-title">{{ optional($r->task)->title }}</div>
                            <div class="wl-req-sub">
                                {{ $incoming ? 'Dari' : 'Ke' }} {{ optional($other)->name ?: '-' }} &middot; {{ optional(optional($other)->division)->name ?: '-' }}
                                &middot; {{ $incoming ? 'diterima' : 'dikirim' }}
                                &middot; {{ ($r->responded_at ?: $r->updated_at)->locale('id')->translatedFormat('d M Y, H:i') }}
                            </div>
                        </div>
                        <span class="wl-badge {{ $r->status === 'accepted' ? 'wl-badge--sudah' : ($r->status === 'declined' ? 'wl-badge--belum' : 'wl-badge--batal') }}">
                            {{ \App\Models\WorkTaskPartnerRequest::LABELS[$r->status] ?? ucfirst($r->status) }}
                        </span>
                    </div>
                @empty
                    <div class="wl-empty">Belum ada riwayat permintaan.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=11"></script>
    <script>
        (function () {
            var form = document.getElementById('wl-export-form');
            if (!form) return;
            function syncCats() {
                var t = form.querySelector('input[name="type"]:checked');
                var box = document.getElementById('wl-exp-cats');
                if (box) box.style.display = (t && t.value === 'project') ? 'none' : '';
            }
            form.querySelectorAll('input[name="type"]').forEach(function (r) { r.addEventListener('change', syncCats); });
            syncCats();
            form.querySelectorAll('[data-exp-cats]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var on = b.getAttribute('data-exp-cats') === 'all';
                    form.querySelectorAll('input[name="categories[]"]').forEach(function (c) { c.checked = on; });
                });
            });
        })();
    </script>
@endsection
