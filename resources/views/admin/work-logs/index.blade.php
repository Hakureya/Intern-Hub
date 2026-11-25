@extends('layouts.master')

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=19" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Log Pekerjaan</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active">Log Pekerjaan</li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="wl">
        <div class="wl-head">
            <div>
                <h2 class="wl-title">Log Pekerjaan</h2>
                <p class="wl-sub">
                    Daftar tugas peserta magang per bulan.
                    @if ($isMentor)
                        Hanya menampilkan peserta divisi {{ optional($viewer->division)->name ?: 'Anda' }}.
                    @endif
                </p>
            </div>
            <div class="wl-head-actions">
                @if ($type === 'daily')
                    <button type="button" class="wl-btn" data-wl-open="#wl-cat-modal"><i class="mdi mdi-tag-multiple-outline"></i> Atur kategori</button>
                    <a href="{{ route('work-logs.create', ['type' => 'daily']) }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-plus"></i> Tambah tugas harian</a>
                @else
                    <a href="{{ route('work-logs.create') }}" class="wl-btn wl-btn--primary">
                        <i class="mdi mdi-plus"></i> Tambah tugas proyek
                    </a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('work-logs.index') }}" class="wl-filters">
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
            @unless ($isMentor)
                <div class="wl-f">
                    <label class="wl-label" for="wl-division">Divisi</label>
                    <select id="wl-division" name="division_id" class="wl-select" onchange="this.form.submit()">
                        <option value="">Semua divisi</option>
                        @foreach ($divisions as $d)
                            <option value="{{ $d->id }}" {{ $divisionId === $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="wl-f" style="flex: 0 0 auto; display: flex; gap: 8px;">
                <button type="submit" class="wl-btn wl-btn--primary">Cari</button>
                <a href="{{ route('work-logs.index', ['type' => $type]) }}" class="wl-btn">Reset</a>
            </div>
        </form>

        {{-- Tab bentuk tugas --}}
        <?php $tq = array_filter(['month' => $month, 'q' => $search, 'user_id' => $userId, 'division_id' => $divisionId]); ?>
        <div class="wl-typetabs">
            <a class="wl-typetab {{ $type === 'project' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $tq + ['type' => 'project']) }}">
                <i class="mdi mdi-briefcase-outline"></i> Tugas Proyek <small>{{ $typeCounts['project'] }}</small></a>
            <a class="wl-typetab {{ $type === 'daily' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $tq + ['type' => 'daily']) }}">
                <i class="mdi mdi-calendar-check-outline"></i> Tugas Harian <small>{{ $typeCounts['daily'] }}</small></a>
        </div>

        {{-- Filter status (tugas dengan minimal satu peserta berstatus tsb); filter lain tetap terbawa. --}}
        <div class="wl-stats">
            {{-- Memakai tag PHP biasa karena direktif php satu baris bentrok dengan blok php di file ini. --}}
            <?php $sq = array_filter(['month' => $month, 'q' => $search, 'user_id' => $userId, 'division_id' => $divisionId]) + ['type' => $type]; ?>
            <a class="wl-stat {{ $status === '' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $sq) }}">
                <b>{{ $stats['semua'] }}</b><span>Semua tugas</span></a>
            <a class="wl-stat {{ $status === 'sudah' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $sq + ['status' => 'sudah']) }}">
                <b>{{ $stats['sudah'] }}</b><span>Sudah selesai</span></a>
            <a class="wl-stat {{ $status === 'menunggu' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $sq + ['status' => 'menunggu']) }}">
                <b>{{ $stats['menunggu'] }}</b><span>Menunggu persetujuan</span></a>
            <a class="wl-stat {{ $status === 'belum' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $sq + ['status' => 'belum']) }}">
                <b>{{ $stats['belum'] }}</b><span>Belum dikerjakan</span></a>
            <a class="wl-stat {{ $status === 'batal' ? 'is-active' : '' }}" href="{{ route('work-logs.index', $sq + ['status' => 'batal']) }}">
                <b>{{ $stats['batal'] }}</b><span>Batal</span></a>
        </div>

        <div class="wl-list">
            @forelse ($groups as $date => $groupRows)
                @php
                    $day = \Carbon\Carbon::parse($date)->locale('id');
                @endphp
                <div class="wl-day-head">
                    <b>{{ $day->translatedFormat($day->isCurrentYear() ? 'd M' : 'd M Y') }}</b>
                    <span>{{ $day->isToday() ? 'Hari ini' : ($day->isYesterday() ? 'Kemarin' : ucfirst($day->diffForHumans())) }}</span>
                </div>
                @foreach ($groupRows as $row)
                @php
                    $task = $row['task'];
                    $late = $task->isPastDeadline();
                    $names = $row['assignees']->map(function ($a) { return optional($a->user)->name; })->filter()->values();
                @endphp
                <div class="wl-row {{ $row['allCancelled'] ? 'is-batal' : '' }}">
                    <a class="wl-row-link" href="{{ route('work-logs.show', $task) }}" aria-label="Buka {{ $task->title }}"></a>
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
                                @if ($task->deadline)
                                    @if ($late && ! $row['allCancelled'])
                                        <span class="wl-badge wl-badge--late">Lewat tenggat</span>
                                    @elseif ($row['counts']['belum'] + $row['counts']['menunggu'] > 0)
                                        @include('includes.work-countdown', ['task' => $task])
                                    @endif
                                @endif
                            </span>
                            <?php $lastSubmitted = $task->assignees->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first(); ?>
                            @if ($lastSubmitted)
                                <span><i class="mdi mdi-send-check-outline"></i>
                                    {{ $task->assignees->count() > 1 ? 'Pengajuan terakhir' : 'Diajukan' }} {{ $lastSubmitted->submitted_at->locale('id')->translatedFormat('d M Y') }}
                                </span>
                            @endif
                            <span><i class="mdi mdi-account-edit-outline"></i> Dibuat oleh: {{ optional($task->creator)->name ?: '-' }}</span>
                            <span><i class="mdi mdi-account-outline"></i>
                                {{ $names->take(2)->implode(', ') }}@if ($names->count() > 2) +{{ $names->count() - 2 }} lainnya @endif
                            </span>
                            @if ($task->require_attachment)
                                <span><i class="mdi mdi-paperclip"></i> Wajib lampiran</span>
                            @endif
                            @if ($task->is_self)
                                <span class="wl-badge wl-badge--info">{{ $task->isDaily() ? 'Harian' : 'Dicatat peserta' }}</span>
                            @endif
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
                    @include('includes.work-people', ['people' => $row['people']])
                </div>
                @endforeach
            @empty
                <div class="wl-empty">
                    <i class="mdi mdi-clipboard-text-outline" style="font-size: 34px;"></i>
                    <p class="mb-0 mt-2">{{ $status !== '' || $search !== '' || $userId || $divisionId ? 'Tidak ada tugas yang cocok dengan filter.' : ($type === 'daily' ? 'Belum ada tugas harian pada bulan ini.' : 'Belum ada tugas proyek pada bulan ini.') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($type === 'daily')
    {{-- Atur kategori tugas harian: mentor = divisinya, admin/super admin = semua divisi. --}}
    <div class="wl-modal wl" id="wl-cat-modal">
        <div class="wl-modal-box" style="width: min(520px, 100%);">
            <div class="wl-modal-head">
                <h5>Kategori tugas harian</h5>
                <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
            </div>
            <div class="wl-modal-body">
                @unless ($isMentor)
                    <div class="wl-field">
                        <label class="wl-label" for="wl-cat-division">Divisi</label>
                        <select id="wl-cat-division" class="wl-select" data-cat-division></select>
                    </div>
                @else
                    <div class="wl-hint" style="margin-bottom: 8px;">Divisi {{ optional($viewer->division)->name }}</div>
                @endunless
                <div data-cat-list></div>
                <div style="display: flex; gap: 8px; margin-top: 12px;">
                    <input type="text" class="wl-input" maxlength="40" placeholder="Nama kategori baru" data-cat-new>
                    <button type="button" class="wl-btn wl-btn--primary" data-cat-add>Tambah</button>
                </div>
                <div class="wl-error" data-cat-err hidden></div>
                <div class="wl-hint" style="margin-top: 10px;">Mengubah nama kategori ikut mengganti tugas lama di divisi ini. Menghapus kategori tidak mengubah tugas lama.</div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=11"></script>

    @if ($type === 'daily')
    <script>
        (function () {
            var modal = document.getElementById('wl-cat-modal');
            if (!modal) { return; }
            var listEl = modal.querySelector('[data-cat-list]'), errEl = modal.querySelector('[data-cat-err]'),
                selEl = modal.querySelector('[data-cat-division]'), inEl = modal.querySelector('[data-cat-new]'),
                token = '{{ csrf_token() }}', base = '{{ url('/work-categories') }}', current = null, loaded = false;

            function api(method, url, body) {
                return fetch(url, { method: method, credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body ? JSON.stringify(body) : undefined
                }).then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (j) {
                        if (!r.ok) {
                            var first = j.errors ? Object.keys(j.errors).map(function (k) { return j.errors[k][0]; })[0] : null;
                            throw new Error(first || j.message || 'Terjadi kesalahan.');
                        }
                        return j;
                    });
                });
            }
            function fail(e) { errEl.textContent = e.message; errEl.hidden = false; }
            function btn(text, cls, fn) {
                var b = document.createElement('button'); b.type = 'button'; b.textContent = text; if (cls) { b.className = cls; } b.addEventListener('click', fn); return b;
            }
            function render(d) {
                current = d.division_id; errEl.hidden = true;
                if (selEl) {
                    if (!selEl.options.length) {
                        d.divisions.forEach(function (x) { var o = document.createElement('option'); o.value = x.id; o.textContent = x.name; selEl.appendChild(o); });
                    }
                    selEl.value = d.division_id;
                }
                listEl.innerHTML = '';
                if (!d.categories.length) { listEl.innerHTML = '<div class="wl-hint">Belum ada kategori.</div>'; }
                d.categories.forEach(function (c) {
                    var row = document.createElement('div'); row.className = 'wl-cat-row';
                    var name = document.createElement('span'); name.textContent = c.name;
                    var uses = document.createElement('small'); uses.textContent = c.uses + ' tugas';
                    row.appendChild(name); row.appendChild(uses);
                    row.appendChild(btn('Ubah', '', function () {
                        var v = window.prompt('Nama kategori', c.name);
                        if (v === null || !v.trim() || v.trim() === c.name) { return; }
                        api('PUT', base + '/' + c.id, { name: v.trim() }).then(render).catch(fail);
                    }));
                    row.appendChild(btn('Hapus', 'is-del', function () {
                        if (!window.confirm('Hapus kategori "' + c.name + '"? Tugas lama tetap menyimpan namanya.')) { return; }
                        api('DELETE', base + '/' + c.id).then(render).catch(fail);
                    }));
                    listEl.appendChild(row);
                });
            }
            function load(id) { api('GET', base + (id ? '?division_id=' + id : '')).then(render).catch(fail); }

            document.querySelectorAll('[data-wl-open="#wl-cat-modal"]').forEach(function (b) {
                b.addEventListener('click', function () { if (!loaded) { loaded = true; load(); } });
            });
            if (selEl) { selEl.addEventListener('change', function () { load(selEl.value); }); }
            modal.querySelector('[data-cat-add]').addEventListener('click', function () {
                var v = inEl.value.trim(); if (!v) { return; }
                api('POST', base, { division_id: current, name: v }).then(function (d) { inEl.value = ''; render(d); }).catch(fail);
            });
            inEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); modal.querySelector('[data-cat-add]').click(); } });
        })();
    </script>
    @endif
@endsection
