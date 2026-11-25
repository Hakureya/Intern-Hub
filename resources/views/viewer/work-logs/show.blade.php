@extends('layouts.viewer')

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=20" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="wl" style="margin-top: 20px;">
        <div class="wl-head">
            <a href="{{ route('viewer.work-logs.index', ['type' => $task->type, 'month' => optional($task->endDate() ?: $task->workedDate())->format('Y-m')]) }}" class="wl-btn">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="wl-detail-grid wl-detail-grid--stack">
            <div class="wl-card">
                <div class="wl-assign-head">
                    <h3 class="wl-detail-title"><span class="wl-code">{{ $task->code }}</span> {{ $task->title }}</h3>
                    <div class="wl-head-flags">
                        <span class="wl-badge wl-badge--info">{{ $task->isDaily() ? 'Tugas harian' : ($task->is_self ? 'Dicatat peserta' : 'Dari mentor') }}</span>
                    </div>
                </div>

                <dl class="wl-kv">
                    <dt>ID tugas</dt>
                    <dd>{{ $task->code }}</dd>
                    @if ($task->isDaily())
                        <dt>Jenis tugas</dt>
                    <dd>{{ $task->has_children ? 'Tugas dengan turunan' : 'Pencatatan' }}</dd>
                    @if ($task->has_children)
                        <dt>Mulai</dt>
                        <dd>{{ $task->workedDate()->locale('id')->translatedFormat('l, d F Y') }}</dd>
                    @else
                        <dt>Tanggal pengerjaan</dt>
                        <dd>{{ $task->dateRangeText() }}</dd>
                    @endif
                    @if ($task->deadline)<dt>Tenggat</dt><dd>{{ $task->deadline->locale('id')->translatedFormat('l, d F Y') }}</dd>@endif
                    @if ($task->categoryLabel())<dt>Kategori</dt><dd>{{ $task->categoryLabel() }}</dd>@endif
                    @if ($task->quantity_unit)<dt>Satuan output</dt><dd>{{ $task->quantity_unit }}</dd>@endif
                    @if (! $task->has_children && $task->quantity !== null)<dt>Jumlah output</dt><dd>{{ number_format($task->quantity, 0, ',', '.') }} {{ $task->quantity_unit }}</dd>@endif
                    @if (! $task->has_children && $task->workStatusLabel())
                        <dt>Status pengerjaan</dt>
                        <dd><span class="wl-badge wl-badge--{{ $task->work_status }}">{{ $task->workStatusLabel() }}</span></dd>
                    @endif
                    @else
                        <dt>Periode</dt>
                        <dd>{{ $task->dateRangeText() }}</dd>
                    @endif
                    <dt>Dibuat pada</dt>
                    <dd>{{ $task->created_at->locale('id')->translatedFormat('l, d F Y, H:i') }}</dd>
                    <dt>Dibuat oleh</dt>
                    <dd>{{ optional($task->creator)->name ?: '-' }}</dd>
                </dl>

                <h4 class="wl-sub-title">{{ $task->isDaily() ? 'Deskripsi / rincian pekerjaan' : 'Instruksi' }}</h4>
                <div class="wl-desc">{{ $task->description }}</div>

                @if ($task->references->isNotEmpty())
                    <h4 class="wl-sub-title">Referensi Tugas</h4>
                    @foreach ($task->references as $ref)
                        @include('includes.work-reference-item', ['ref' => $ref, 'canRemove' => false, 'task' => $task])
                    @endforeach
                @endif

                <?php
                    $results = $assignees->flatMap(function ($a) { return $a->attachments; })
                        ->sortBy('id')
                        ->unique(function ($att) { return $att->type . '|' . ($att->isLink() ? $att->url : $att->path); })
                        ->values();
                    $notes = $assignees->filter(function ($a) { return filled($a->proof_note); });
                ?>
                <h4 class="wl-sub-title">Lampiran bukti</h4>
                @forelse ($results as $att)
                    @include('includes.work-attachment-item', ['att' => $att, 'manage' => false])
                @empty
                    <div class="wl-hint" style="margin-bottom: 10px;">Belum ada lampiran.</div>
                @endforelse
                @foreach ($notes as $a)
                    <div class="wl-note wl-note--blue" style="margin-top: 8px;"><b>Catatan {{ optional($a->user)->name }}:</b> {{ $a->proof_note }}</div>
                @endforeach

                {{-- Turunan tugas / catatan progres: di bawah lampiran bukti. --}}
                @include('includes.work-progress', ['task' => $task])
            </div>

            <div>
                <div class="wl-card wl-pc">
                    <h4 class="wl-section-title">Peserta &amp; status</h4>
                    @foreach ($assignees as $a)
                        <div class="wl-pc-row">
                            <div class="wl-assignee-top wl-pc-sum">
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
                            </div>
                            @if ($a->review_note)
                                <div class="wl-note" style="margin-top: 6px;"><b>Catatan mentor:</b> {{ $a->review_note }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
