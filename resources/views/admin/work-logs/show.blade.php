@extends('layouts.master')

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=11" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-chat.css') }}?v=2" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Detail Tugas</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('work-logs.index') }}">Log Pekerjaan</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="wl">
        <div class="wl-head">
            <a href="{{ route('work-logs.index', ['month' => optional($task->deadline ?: $task->workedDate())->format('Y-m')]) }}" class="wl-btn">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="wl-btn" data-wc-history data-wl-open="#wc-history-modal"><i class="mdi mdi-history"></i> Riwayat</button>
                @if ($canEdit)
                    <a href="{{ route('work-logs.edit', $task) }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-pencil-outline"></i> Edit tugas</a>
                @endif
            </div>
        </div>

        <div class="wc-layout">
        <div class="wc-main">
        <div class="wl-detail-grid wl-detail-grid--stack">
            <div class="wl-card" style="position: relative;">
                <h3 class="wl-detail-title">{{ $task->title }}</h3>

                <dl class="wl-kv">
                    <dt>Tenggat</dt>
                    <dd>
                        @if ($task->deadline)
                            {{ $task->deadline->locale('id')->translatedFormat('l, d F Y') }}, 23:59
                            @if ($task->isPastDeadline())
                                <span class="wl-badge wl-badge--late">Lewat tenggat</span>
                            @elseif ($assignees->where('status', '!=', 'sudah')->count() > 0)
                                @include('includes.work-countdown', ['task' => $task])
                            @endif
                        @else
                            Tanpa tenggat
                        @endif
                    </dd>
                    @if ($task->is_self)
                        <dt>Dikerjakan pada</dt>
                        <dd>{{ $task->workedDate()->locale('id')->translatedFormat('l, d F Y') }}</dd>
                    @endif
                    <dt>Dibuat pada</dt>
                    <dd>{{ $task->created_at->locale('id')->translatedFormat('l, d F Y, H:i') }}</dd>
                    <dt>Dibuat oleh</dt>
                    <dd>{{ optional($task->creator)->name ?: '-' }} {!! $task->is_self ? '<span class="wl-badge wl-badge--info">Dicatat peserta</span>' : '' !!}</dd>
                    <dt>Bukti pengerjaan</dt>
                    <dd>{{ $task->require_attachment ? 'Wajib melampirkan' : 'Opsional' }}</dd>
                </dl>

                <h4 class="wl-sub-title" style="margin-top: 6px;">Deskripsi</h4>
                <div class="wl-desc">{{ $task->description }}</div>

                @if ($task->references->isNotEmpty())
                    <h4 class="wl-sub-title">Referensi tugas</h4>
                    @foreach ($task->references as $ref)
                        @if ($ref->isLink())
                            <div class="wl-work-item">
                                <i class="mdi mdi-link-variant wl-work-ic"></i>
                                <div class="wl-work-name">
                                    <a href="{{ $ref->url }}" target="_blank" rel="noopener noreferrer">{{ $ref->name }}</a>
                                    <small>{{ \Illuminate\Support\Str::limit($ref->url, 60) }}</small>
                                </div>
                            </div>
                        @else
                            <div class="wl-work-item">
                                <i class="mdi {{ $ref->icon() }} wl-work-ic"></i>
                                <div class="wl-work-name"><a href="{{ route('work-logs.file.reference', $ref) }}" target="_blank">{{ $ref->name }}</a></div>
                                <a class="wl-work-dl" href="{{ route('work-logs.file.reference', ['reference' => $ref, 'download' => 1]) }}">Unduh</a>
                            </div>
                        @endif
                    @endforeach
                @endif

                @include('includes.work-people', ['people' => $people])
            </div>

            <div>
                @php
                    $nameList = $assignees->map(function ($a) { return optional($a->user)->name; })->filter()->values();
                @endphp
                <div class="wl-card wl-pc">
                    <h4 class="wl-section-title">Peserta &amp; status</h4>
                    <div class="wl-pc-names">
                        {{ $nameList->take(2)->implode(', ') }}@if ($nameList->count() > 2),
                            <span class="wl-etc" tabindex="0">dll.
                                <span class="wl-tip wl-tip--down">
                                    @foreach ($assignees as $a)
                                        <span class="wl-tip-line"><b>{{ optional($a->user)->name }}</b><em>{{ optional(optional($a->user)->division)->name ?: '-' }}</em></span>
                                    @endforeach
                                </span>
                            </span>
                        @endif
                        <span class="wl-pc-count">{{ $assignees->count() }} peserta</span>
                    </div>

                    @foreach ($assignees as $a)
                        <details class="wl-pc-row">
                            <summary class="wl-assignee-top wl-pc-sum">
                                <span class="wl-avatar">
                                    @if (optional($a->user)->profile_photo_path)
                                        <img src="{{ asset('storage/' . $a->user->profile_photo_path) }}" alt="">
                                    @else
                                        {{ \App\Models\WorkTask::initials(optional($a->user)->name) }}
                                    @endif
                                </span>
                                <div style="flex: 1 1 auto; min-width: 0;">
                                    <div class="wl-assignee-name">{{ optional($a->user)->name }}</div>
                                    <div class="wl-assignee-sub">{{ optional(optional($a->user)->division)->name ?: '-' }}@if ($a->submitted_at) &bull; Diajukan {{ $a->submitted_at->locale('id')->translatedFormat('d M Y') }}@endif</div>
                                </div>
                                <span class="wl-badge wl-badge--{{ $a->status }}">{{ $a->statusLabel() }}</span>
                                <i class="mdi mdi-chevron-down wl-chev"></i>
                            </summary>

                            <div class="wl-pc-detail">
                            <div class="wl-assignee-body">
                                @forelse ($a->attachments as $att)
                                    @include('includes.work-attachment-item', ['att' => $att, 'manage' => false])
                                @empty
                                    <span class="wl-sub">Belum ada lampiran.</span>
                                @endforelse
                                @if ($a->proof_note) <div class="wl-note wl-note--blue"><b>Catatan peserta:</b> {{ $a->proof_note }}</div> @endif
                                @if ($a->submitted_at)
                                    <div class="wl-hint">Diajukan {{ $a->submitted_at->locale('id')->translatedFormat('d M Y H:i') }}</div>
                                @endif
                            </div>
                            @if ($a->review_note)
                                <div class="wl-note {{ $a->status === 'sudah' ? 'wl-note--green' : ($a->status === 'belum' ? 'wl-note--rose' : '') }}"><b>Catatan mentor:</b> {{ $a->review_note }}</div>
                            @endif

                            <form method="POST" action="{{ route('work-logs.status', ['task' => $task, 'assignee' => $a]) }}" class="wl-status-form">
                                @csrf
                                <select name="status" class="wl-select" aria-label="Status {{ optional($a->user)->name }}">
                                    @foreach (\App\Models\WorkTaskAssignee::LABELS as $value => $label)
                                        <option value="{{ $value }}" {{ $a->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="review_note" class="wl-input" maxlength="500" placeholder="Catatan (opsional)">
                                <button type="submit" class="wl-btn wl-btn--primary wl-btn--sm">Simpan</button>
                            </form>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
        </div>{{-- .wc-main --}}
        @include('includes.work-chat', ['task' => $task])
        </div>{{-- .wc-layout --}}
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=10"></script>
    <script src="{{ URL::asset('js/work-chat.js') }}?v=2"></script>
@endsection
