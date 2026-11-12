@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-log.css') }}?v=11" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-chat.css') }}?v=2" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@include('layouts.user-navbar', ['active' => 'worklog'])

<div class="home-wrap">
    <div class="home-shell wl">
        <div class="wl-head">
            <a href="{{ route('user.work-logs.index', ['month' => optional($task->deadline ?: $task->workedDate())->format('Y-m')]) }}" class="wl-btn">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="wl-btn" data-wc-history data-wl-open="#wc-history-modal"><i class="mdi mdi-history"></i> Riwayat</button>
                @if ($canEdit)
                    <a href="{{ route('user.work-logs.edit', $task) }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-pencil-outline"></i> Edit tugas</a>
                @endif
            </div>
        </div>

        <div class="wc-layout">
        <div class="wc-main">
        <div class="wl-card wl-assign">
            <div class="wl-assign-head">
                <h3 class="wl-detail-title">{{ $task->title }}</h3>
                @if ($mine)
                    <span class="wl-badge wl-badge--{{ $mine->status }}">{{ $mine->statusLabel() }}</span>
                @endif
            </div>
            <div class="wl-assign-meta">
                @if ($task->deadline)
                    Tenggat {{ $task->deadline->locale('id')->translatedFormat('d F Y') }}, 23:59
                    @if ($task->isPastDeadline())
                        <span class="wl-badge wl-badge--late">Lewat tenggat</span>
                    @elseif ($mine && $mine->status !== 'sudah')
                        @include('includes.work-countdown', ['task' => $task])
                    @endif
                @else
                    Tanpa tenggat
                @endif
                @if ($task->is_self)
                    <span class="wl-dot">&bull;</span> Dikerjakan pada {{ $task->workedDate()->locale('id')->translatedFormat('d F Y') }}
                @endif
                @if ($mine && $mine->submitted_at)
                    <span class="wl-dot">&bull;</span> Diajukan pada {{ $mine->submitted_at->locale('id')->translatedFormat('d F Y, H:i') }}
                @endif
                <span class="wl-dot">&bull;</span> Dibuat {{ $task->created_at->locale('id')->translatedFormat('d F Y, H:i') }}
                <span class="wl-dot">&bull;</span> Boleh mengumpulkan lebih dari satu lampiran
                <span class="wl-dot">&bull;</span> Dibuat oleh: {{ $task->is_self ? 'Saya' : (optional($task->creator)->name ?: '-') }}
                <span class="wl-badge {{ $task->is_self ? 'wl-badge--info' : 'wl-badge--muted' }}">{{ $task->is_self ? 'Dicatat sendiri' : 'Dari mentor' }}</span>
            </div>

            <h4 class="wl-sub-title">Instruksi</h4>
            <div class="wl-desc">{{ $task->description }}</div>

            @if ($task->references->isNotEmpty())
                <h4 class="wl-sub-title">Referensi Tugas</h4>
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

            @if ($mine)
                <h4 class="wl-sub-title">
                    Pekerjaanmu
                    @if ($task->require_attachment) <small class="req">wajib ada lampiran</small> @endif
                </h4>

                @if ($mine->review_note)
                    <div class="wl-note {{ $mine->status === 'belum' ? 'wl-note--rose' : ($mine->status === 'sudah' ? 'wl-note--green' : '') }}" style="margin-bottom: 12px;">
                        <b>Catatan mentor:</b> {{ $mine->review_note }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="wl-note wl-note--rose" style="margin-bottom: 12px;">
                        @foreach (collect($errors->all())->unique() as $msg) <div>{{ $msg }}</div> @endforeach
                    </div>
                @endif

                @forelse ($mine->attachments as $att)
                    @include('includes.work-attachment-item', ['att' => $att, 'task' => $task, 'manage' => $canWork])
                @empty
                    <div class="wl-hint" style="margin-bottom: 10px;">Belum ada lampiran.</div>
                @endforelse

                @if ($canWork)
                    <div class="wl-attach">
                        <div class="wl-menu-wrap">
                            <button type="button" class="wl-attach-btn" data-wl-menu>
                                <i class="mdi mdi-paperclip"></i> Lampirkan <i class="mdi mdi-chevron-down"></i>
                            </button>
                            <div class="wl-menu wl-menu--left">
                                <button type="button" data-wl-open="#wl-link-modal"><i class="mdi mdi-link-variant"></i> Tautan</button>
                                <hr>
                                <button type="button" data-wl-upload="#wl-upload-input"><i class="mdi mdi-paperclip"></i> Unggah dari perangkat</button>
                            </div>
                        </div>
                    </div>
                    <form id="wl-upload-form" method="POST" enctype="multipart/form-data" hidden
                        action="{{ route('user.work-logs.attachments.store', $task) }}">
                        @csrf
                        <input type="file" id="wl-upload-input" name="files[]" multiple>
                    </form>
                @endif

                @if ($mine->status === 'belum')
                    <form method="POST" action="{{ route('user.work-logs.submit', $task) }}" class="wl-submit-box">
                        @csrf
                        <label class="wl-label" for="proof_note">Catatan <small>(opsional)</small></label>
                        <textarea id="proof_note" name="proof_note" class="wl-textarea" style="min-height: 76px;" maxlength="1000">{{ old('proof_note', $mine->proof_note) }}</textarea>
                        <button type="submit" class="wl-btn wl-btn--primary" style="margin-top: 12px;">
                            <i class="mdi mdi-check-circle-outline"></i> Ajukan tandai selesai
                        </button>
                    </form>
                @elseif ($mine->status === 'menunggu')
                    @if ($mine->proof_note) <div class="wl-note wl-note--blue" style="margin-top: 12px;"><b>Catatanmu:</b> {{ $mine->proof_note }}</div> @endif
                    <div class="wl-hint" style="margin-top: 10px;">Menunggu persetujuan mentor. Kamu masih bisa menambah atau mengganti lampiran.</div>
                    <form method="POST" action="{{ route('user.work-logs.withdraw', $task) }}" style="margin-top: 12px;"
                        data-confirm="Status kembali menjadi Belum dan lampiran tetap tersimpan. Kamu bisa mengajukan lagi kapan saja." data-confirm-title="Tarik pengajuan?" data-confirm-ok="Ya, tarik" data-confirm-tone="danger">
                        @csrf
                        <button type="submit" class="wl-btn wl-btn--danger"><i class="mdi mdi-undo-variant"></i> Tarik pengajuan</button>
                    </form>
                @else
                    @if ($mine->proof_note) <div class="wl-note wl-note--blue" style="margin-top: 12px;"><b>Catatanmu:</b> {{ $mine->proof_note }}</div> @endif
                    <div class="wl-hint" style="margin-top: 10px;">Tugas sudah disetujui mentor.</div>
                @endif
            @endif

            @include('includes.work-people', ['people' => $people])
        </div>
        </div>{{-- .wc-main --}}
        @include('includes.work-chat', ['task' => $task])
        </div>{{-- .wc-layout --}}
    </div>
</div>

@if ($mine && $canWork)
    <div class="wl-modal wl {{ $errors->has('link_url') ? 'is-open' : '' }}" id="wl-link-modal">
        <div class="wl-modal-box" style="width: min(480px, 100%);">
            <div class="wl-modal-head">
                <h5>Tambah tautan</h5>
                <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
            </div>
            <form method="POST" action="{{ route('user.work-logs.attachments.store', $task) }}" class="wl-modal-body">
                @csrf
                <div class="wl-field">
                    <label class="wl-label" for="link_url">Alamat tautan <span class="req">*</span></label>
                    <input type="url" id="link_url" name="link_url" class="wl-input" maxlength="500" required placeholder="https://..." value="{{ old('link_url') }}">
                    @error('link_url') <div class="wl-error">{{ $message }}</div> @enderror
                </div>
                <div class="wl-field">
                    <label class="wl-label" for="link_label">Nama tampilan <small>(opsional)</small></label>
                    <input type="text" id="link_label" name="link_label" class="wl-input" maxlength="120" value="{{ old('link_label') }}">
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="wl-btn" data-wl-close>Batal</button>
                    <button type="submit" class="wl-btn wl-btn--primary">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=10"></script>
    <script src="{{ URL::asset('js/work-chat.js') }}?v=2"></script>
@endsection
