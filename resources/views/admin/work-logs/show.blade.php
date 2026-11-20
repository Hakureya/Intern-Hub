@extends('layouts.master')

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=16" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-chat.css') }}?v=3" rel="stylesheet" type="text/css" />
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
            <a href="{{ route('work-logs.index', ['month' => optional($task->endDate() ?: $task->workedDate())->format('Y-m')]) }}" class="wl-btn">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="wl-btn" data-wc-history data-wl-open="#wc-history-modal"><i class="mdi mdi-history"></i> Riwayat</button>
                @if ($canCancel)
                    <form method="POST" action="{{ route('work-logs.cancel', $task) }}" style="margin: 0;"
                        data-confirm="Batalkan tugas &quot;{{ $task->title }}&quot; untuk {{ $assignees->where('status', '!=', 'batal')->count() }} peserta? Tugas tidak akan tampil lagi di halaman peserta, tetapi tetap ada di halaman ini. Anda bisa mengaktifkannya kembali dengan mengubah status peserta."
                        data-confirm-title="Batalkan tugas?" data-confirm-ok="Ya, batalkan tugas" data-confirm-level="warning" data-confirm-tone="danger">
                        @csrf
                        <button type="submit" class="wl-btn wl-btn--danger"><i class="mdi mdi-cancel"></i> Batalkan tugas</button>
                    </form>
                @endif
                @if ($canEdit)
                    <a href="{{ route('work-logs.edit', $task) }}" class="wl-btn wl-btn--primary"><i class="mdi mdi-pencil-outline"></i> Edit tugas</a>
                @endif
            </div>
        </div>

        <div class="wc-layout">
        <div class="wc-main">
        <div class="wl-detail-grid wl-detail-grid--stack">
            <div class="wl-card" style="position: relative;">
                <div class="wl-assign-head">
                    <h3 class="wl-detail-title"><span class="wl-code">{{ $task->code }}</span> {{ $task->title }}</h3>
                    {{-- Pojok kanan atas: penanda dicatat peserta, waktu tenggat, dan status pekerjaan (ringkasan seluruh peserta). --}}
                    <div class="wl-head-flags">
                        @if ($task->is_self)
                            <span class="wl-badge wl-badge--info">{{ $task->isDaily() ? 'Tugas harian' : 'Dicatat peserta' }}</span>
                        @endif
                        @if ($allCancelled)
                            {{-- dibatalkan: tidak ada hitung mundur / penanda lewat tenggat --}}
                        @elseif ($task->isPastDeadline())
                            <span class="wl-badge wl-badge--late">Lewat tenggat</span>
                        @elseif ($task->deadline && $assignees->whereNotIn('status', ['sudah', 'batal'])->count() > 0)
                            @include('includes.work-countdown', ['task' => $task])
                        @endif
                        <?php $statusCounts = $assignees->countBy('status'); ?>
                        @if ($assignees->count() === 1)
                            <span class="wl-badge wl-badge--{{ $assignees->first()->status }}">{{ $assignees->first()->statusLabel() }}</span>
                        @else
                            @foreach (['menunggu', 'sudah', 'belum', 'batal'] as $st)
                                @if ($statusCounts->get($st))
                                    <span class="wl-badge wl-badge--{{ $st }}">{{ $statusCounts->get($st) }} {{ mb_strtolower(\App\Models\WorkTaskAssignee::LABELS[$st]) }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                @if ($allCancelled)
                    <div class="wl-note" style="margin-bottom: 4px;"><b>Tugas ini dibatalkan.</b> Tugas tidak tampil di halaman peserta. Ubah status peserta di bawah untuk mengaktifkannya kembali.</div>
                @endif

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
                    <dd>{{ optional($task->creator)->name ?: '-' }}</dd>
                    @unless ($task->isDaily())
                        <dt>Bukti pengerjaan</dt>
                        <dd>{{ $task->require_attachment ? 'Wajib melampirkan' : 'Opsional' }}</dd>
                    @endunless
                </dl>

                <h4 class="wl-sub-title">Instruksi</h4>
                <div class="wl-desc">{{ $task->description }}</div>

                {{-- Sama dengan tampilan peserta: referensi dari mentor maupun peserta; mentor juga bisa menambah langsung di sini. --}}
                <h4 class="wl-sub-title">Referensi Tugas</h4>

                @foreach ($task->references as $ref)
                    @include('includes.work-reference-item', [
                        'ref' => $ref,
                        'canRemove' => $canAddRef,   // mentor/admin boleh menghapus referensi siapa pun
                        'task' => $task,
                        'destroyRoute' => 'work-logs.references.destroy',
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
                        action="{{ route('work-logs.references.store', $task) }}">
                        @csrf
                        <input type="file" id="wl-ref-upload-input" name="reference_files[]" multiple data-wl-autosubmit>
                    </form>
                @endif

                {{-- Hasil pekerjaan peserta (terpisah dari referensi). Salinan identik pada tugas bersama ditampilkan sekali. --}}
                <?php
                    $results = $assignees->flatMap(function ($a) { return $a->attachments; })
                        ->sortBy('id')
                        ->unique(function ($att) { return $att->type . '|' . ($att->isLink() ? $att->url : $att->path); })
                        ->values();
                    $notes = $assignees->filter(function ($a) { return filled($a->proof_note); });
                ?>
                <h4 class="wl-sub-title">
                    Hasil pekerjaan
                    @if ($task->require_attachment) <small class="req">wajib ada lampiran</small> @endif
                </h4>
                @forelse ($results as $att)
                    @include('includes.work-attachment-item', ['att' => $att, 'manage' => false])
                @empty
                    <div class="wl-hint" style="margin-bottom: 10px;">Belum ada lampiran.</div>
                @endforelse
                @foreach ($notes as $a)
                    <div class="wl-note wl-note--blue" style="margin-top: 8px;"><b>Catatan {{ optional($a->user)->name }}:</b> {{ $a->proof_note }}</div>
                @endforeach

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
                            @if ($a->review_note)
                                <div class="wl-note {{ $a->status === 'sudah' ? 'wl-note--green' : ($a->status === 'belum' ? 'wl-note--rose' : '') }}"><b>Catatan mentor:</b> {{ $a->review_note }}</div>
                            @endif

                            <form method="POST" action="{{ route('work-logs.status', ['task' => $task, 'assignee' => $a]) }}" class="wl-status-form"
                                data-wl-cancel-confirm="Batalkan tugas untuk {{ optional($a->user)->name }}? Tugas tidak akan tampil lagi di halaman peserta tersebut, tetapi tetap ada di halaman ini. Mengubah statusnya kembali akan menampilkannya lagi di halaman peserta.">
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
@if ($canAddRef)
    <div class="wl-modal wl {{ $errors->has('reference_link') ? 'is-open' : '' }}" id="wl-ref-modal">
        <div class="wl-modal-box" style="width: min(480px, 100%);">
            <div class="wl-modal-head">
                <h5>Tambah tautan referensi</h5>
                <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
            </div>
            <form method="POST" action="{{ route('work-logs.references.store', $task) }}" class="wl-modal-body">
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
    <script src="{{ URL::asset('js/work-log.js') }}?v=12"></script>
    <script src="{{ URL::asset('js/work-chat.js') }}?v=4"></script>
@endsection
