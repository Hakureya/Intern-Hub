@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-log.css') }}?v=16" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-chat.css') }}?v=3" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@include('layouts.user-navbar', ['active' => 'worklog'])

<div class="home-wrap">
    <div class="home-shell wl">
        <div class="wl-head">
            <a href="{{ route('user.work-logs.index', ['month' => optional($task->endDate() ?: $task->workedDate())->format('Y-m')]) }}" class="wl-btn">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="wl-btn" data-wc-history data-wl-open="#wc-history-modal"><i class="mdi mdi-history"></i> Riwayat</button>
                @if ($canCancel)
                    <form method="POST" action="{{ route('user.work-logs.cancel', $task) }}" style="margin: 0;"
                        data-confirm="Batalkan tugas &quot;{{ $task->title }}&quot;? Tugas akan hilang dari log pekerjaan Anda{{ $task->assignees->count() > 1 ? ' dan rekan kolaborasi' : '' }}, tetapi tetap terlihat oleh mentor. Hanya mentor yang dapat mengaktifkannya kembali."
                        data-confirm-title="Batalkan tugas?" data-confirm-ok="Ya, batalkan tugas" data-confirm-level="warning" data-confirm-tone="danger">
                        @csrf
                        <button type="submit" class="wl-btn wl-btn--danger"><i class="mdi mdi-cancel"></i> Batalkan tugas</button>
                    </form>
                @endif
                @if ($canEdit)
                    <a href="{{ route('user.work-logs.edit', $task) }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-pencil-outline"></i> Edit tugas</a>
                @endif
            </div>
        </div>

        <div class="wc-layout">
        <div class="wc-main">
        <div class="wl-card wl-assign">
            <div class="wl-assign-head">
                <h3 class="wl-detail-title"><span class="wl-code">{{ $task->code }}</span> {{ $task->title }}</h3>
                {{-- Pojok kanan atas: penanda dicatat sendiri, waktu tenggat, dan status pekerjaan. --}}
                <div class="wl-head-flags">
                    @if ($task->is_self)
                        <span class="wl-badge wl-badge--info">{{ $task->isDaily() ? 'Tugas harian' : 'Dicatat sendiri' }}</span>
                    @endif
                    @if ($task->isPastDeadline())
                        <span class="wl-badge wl-badge--late">Lewat tenggat</span>
                    @elseif ($task->deadline && $mine && $mine->status !== 'sudah')
                        @include('includes.work-countdown', ['task' => $task])
                    @endif
                    @if ($mine)
                        <span class="wl-badge wl-badge--{{ $mine->status }}">{{ $mine->statusLabel() }}</span>
                    @endif
                </div>
            </div>
            <dl class="wl-kv">
                <dt>ID tugas</dt>
                <dd>{{ $task->code }}</dd>
                @if ($task->isDaily())
                    <dt>Tanggal pengerjaan</dt>
                    <dd>{{ $task->workedDate()->locale('id')->translatedFormat('l, d F Y') }}</dd>
                    @if ($task->categoryLabel())<dt>Kategori</dt><dd>{{ $task->categoryLabel() }}</dd>@endif
                    @if ($task->quantity !== null)<dt>Jumlah output</dt><dd>{{ number_format($task->quantity, 0, ',', '.') }} {{ $task->quantity_unit }}</dd>@endif
                    <dt>Status pengerjaan</dt>
                    <dd><span class="wl-badge wl-badge--{{ $task->work_status }}">{{ $task->workStatusLabel() }}</span></dd>
                @else
                <dt>Tanggal mulai</dt>
                <dd>{{ $task->workedDate()->locale('id')->translatedFormat('l, d F Y') }}</dd>
                <dt>Tanggal selesai</dt>
                <dd>
                    @if ($task->endDate())
                        {{ $task->endDate()->locale('id')->translatedFormat('l, d F Y') }}@if ($task->deadline), 23:59 <small class="wl-sub-note">(tenggat)</small>@endif
                    @else
                        Tanpa tenggat
                    @endif
                </dd>
                @endif
                <dt>Dibuat pada</dt>
                <dd>{{ $task->created_at->locale('id')->translatedFormat('l, d F Y, H:i') }}</dd>
                <dt>Dibuat oleh</dt>
                <dd>{{ (int) $task->created_by === (int) auth()->id() ? 'Saya' : (optional($task->creator)->name ?: '-') }}</dd>
                @unless ($task->isDaily())
                    <dt>Bukti pengerjaan</dt>
                    <dd>{{ $task->require_attachment ? 'Wajib melampirkan' : 'Opsional' }}</dd>
                @endunless
            </dl>

            <h4 class="wl-sub-title">Instruksi</h4>
            <div class="wl-desc">{{ $task->description }}</div>

            {{-- Referensi materi: mentor dan peserta (yang mengerjakan tugas ini) boleh menambahkan. --}}
            @if ($canAddRef || $task->references->isNotEmpty())
                <h4 class="wl-sub-title">Referensi Tugas</h4>

                @foreach ($task->references as $ref)
                    @include('includes.work-reference-item', [
                        'ref' => $ref,
                        'canRemove' => $canAddRef && (int) $ref->uploaded_by === (int) auth()->id(),
                        'task' => $task,
                    ])
                @endforeach

                @if ($task->references->isEmpty())
                    <div class="wl-hint" style="margin-bottom: 10px;">Belum ada referensi materi.</div>
                @endif

                <?php
                    $refErrors = collect($errors->getBag('default')->getMessages())
                        ->filter(function ($msgs, $key) { return strpos($key, 'reference_') === 0; })
                        ->flatten()->unique();
                ?>
                @foreach ($refErrors as $msg)
                    <div class="wl-error">{{ $msg }}</div>
                @endforeach

                @if ($canAddRef)
                    <div class="wl-attach">
                        <div class="wl-menu-wrap">
                            <button type="button" class="wl-attach-btn" data-wl-menu>
                                <i class="mdi mdi-book-plus-outline"></i> Tambah referensi <i class="mdi mdi-chevron-down"></i>
                            </button>
                            <div class="wl-menu wl-menu--left">
                                <button type="button" data-wl-open="#wl-ref-modal"><i class="mdi mdi-link-variant"></i> Tautan</button>
                                <hr>
                                <button type="button" data-wl-upload="#wl-ref-upload-input"><i class="mdi mdi-paperclip"></i> Unggah dari perangkat</button>
                            </div>
                        </div>
                    </div>
                    <form id="wl-ref-upload-form" method="POST" enctype="multipart/form-data" hidden
                        action="{{ route('user.work-logs.references.store', $task) }}">
                        @csrf
                        <input type="file" id="wl-ref-upload-input" name="reference_files[]" multiple data-wl-autosubmit>
                    </form>
                @endif
            @endif

            @if ($mine)
                <h4 class="wl-sub-title">
                    Hasil pekerjaan
                    @if ($task->require_attachment) <small class="req">wajib ada lampiran</small> @endif
                </h4>

                @if ($mine->review_note)
                    <div class="wl-note {{ $mine->status === 'belum' ? 'wl-note--rose' : ($mine->status === 'sudah' ? 'wl-note--green' : '') }}" style="margin-bottom: 12px;">
                        <b>Catatan mentor:</b> {{ $mine->review_note }}
                    </div>
                @endif

                <?php
                    $workErrors = collect($errors->getBag('default')->getMessages())
                        ->reject(function ($msgs, $key) { return strpos($key, 'reference_') === 0; })
                        ->flatten()->unique();
                ?>
                @if ($workErrors->isNotEmpty())
                    <div class="wl-note wl-note--rose" style="margin-bottom: 12px;">
                        @foreach ($workErrors as $msg) <div>{{ $msg }}</div> @endforeach
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
                    <div class="wl-hint" style="margin-top: 10px;">
                        @if ($mine->submitted_at) Diajukan pada {{ $mine->submitted_at->locale('id')->translatedFormat('d F Y, H:i') }}. @endif
                        Menunggu persetujuan mentor. Kamu masih bisa menambah atau mengganti lampiran.
                    </div>
                    <form method="POST" action="{{ route('user.work-logs.withdraw', $task) }}" style="margin-top: 12px;"
                        data-confirm="Status kembali menjadi Belum dan lampiran tetap tersimpan. Kamu bisa mengajukan lagi kapan saja." data-confirm-title="Tarik pengajuan?" data-confirm-ok="Ya, tarik" data-confirm-tone="danger">
                        @csrf
                        <button type="submit" class="wl-btn wl-btn--danger"><i class="mdi mdi-undo-variant"></i> Tarik pengajuan</button>
                    </form>
                @else
                    @if ($mine->proof_note) <div class="wl-note wl-note--blue" style="margin-top: 12px;"><b>Catatanmu:</b> {{ $mine->proof_note }}</div> @endif
                    <div class="wl-hint" style="margin-top: 10px;">
                        @if ($mine->submitted_at) Diajukan pada {{ $mine->submitted_at->locale('id')->translatedFormat('d F Y, H:i') }}. @endif
                        Tugas sudah disetujui mentor.
                    </div>
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
@if ($canAddRef)
    <div class="wl-modal wl {{ $errors->has('reference_link') ? 'is-open' : '' }}" id="wl-ref-modal">
        <div class="wl-modal-box" style="width: min(480px, 100%);">
            <div class="wl-modal-head">
                <h5>Tambah tautan referensi</h5>
                <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
            </div>
            <form method="POST" action="{{ route('user.work-logs.references.store', $task) }}" class="wl-modal-body">
                @csrf
                <div class="wl-field">
                    <label class="wl-label" for="reference_link">Alamat tautan <span class="req">*</span></label>
                    <input type="url" id="reference_link" name="reference_link" class="wl-input" maxlength="500" required placeholder="https://..." value="{{ old('reference_link') }}">
                    @error('reference_link') <div class="wl-error">{{ $message }}</div> @enderror
                </div>
                <div class="wl-field">
                    <label class="wl-label" for="reference_link_label">Nama tampilan <small>(opsional)</small></label>
                    <input type="text" id="reference_link_label" name="reference_link_label" class="wl-input" maxlength="120" value="{{ old('reference_link_label') }}">
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
    <script src="{{ URL::asset('js/work-log.js') }}?v=11"></script>
    <script src="{{ URL::asset('js/work-chat.js') }}?v=4"></script>
@endsection
