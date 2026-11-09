@extends('layouts.master')

@php($isEdit = !is_null($task))

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=7" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">{{ $isEdit ? 'Edit tugas' : 'Tambah tugas' }}</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('work-logs.index') }}">Log Pekerjaan</a></li>
            <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="wl">
        <div class="wl-head">
            <div>
                <h2 class="wl-title">{{ $isEdit ? 'Edit tugas' : 'Tambah tugas baru' }}</h2>
                <p class="wl-sub">
                    @if ($isEdit)
                        Tugas hanya bisa diedit sebelum tenggat. Setiap perubahan mengembalikan status seluruh peserta menjadi Belum.
                    @else
                        Isi detail pekerjaan, lalu pilih satu atau beberapa peserta magang yang akan mengerjakannya.
                    @endif
                </p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" data-wl-picker
            action="{{ $isEdit ? route('work-logs.update', $task) : route('work-logs.store') }}"
            @if ($isEdit) data-confirm="Menyimpan perubahan akan mengembalikan status semua peserta menjadi Belum. Lampiran mereka tetap tersimpan." data-confirm-title="Simpan perubahan?" data-confirm-ok="Ya, simpan" data-confirm-level="warning" data-confirm-tone="normal" @endif>
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div class="wl-card">
                <div class="wl-field">
                    <label class="wl-label" for="title">Judul tugas <span class="req">*</span></label>
                    <input type="text" id="title" name="title" class="wl-input" maxlength="150" required
                        value="{{ old('title', optional($task)->title) }}" placeholder="Contoh: Buat dokumentasi API">
                    @error('title') <div class="wl-error">{{ $message }}</div> @enderror
                </div>

                <div class="wl-field">
                    <label class="wl-label" for="description">Deskripsi <span class="req">*</span></label>
                    <textarea id="description" name="description" class="wl-textarea" maxlength="5000" required
                        placeholder="Jelaskan ruang lingkup dan hasil yang diharapkan.">{{ old('description', optional($task)->description) }}</textarea>
                    @error('description') <div class="wl-error">{{ $message }}</div> @enderror
                </div>

                <div class="wl-form-grid">
                    <div class="wl-field">
                        <label class="wl-label" for="deadline">Tenggat <small>(opsional)</small></label>
                        <input type="date" id="deadline" name="deadline" class="wl-input"
                            @unless ($isEdit) min="{{ now()->toDateString() }}" @endunless
                            value="{{ old('deadline', optional(optional($task)->deadline)->toDateString()) }}">
                        @error('deadline') <div class="wl-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="wl-field">
                        <label class="wl-label" for="reference_file">Lampiran file <small>(opsional, referensi tugas)</small></label>
                        <input type="file" id="reference_file" name="reference_file" class="wl-input">
                        @if ($isEdit && $task->reference_file_path)
                            <div class="wl-hint">
                                File saat ini: <a href="{{ route('work-logs.file.reference', $task) }}" target="_blank">{{ $task->reference_file_name }}</a>
                                &middot; <label class="mb-0"><input type="checkbox" name="remove_reference_file" value="1"> hapus</label>
                            </div>
                        @endif
                        @error('reference_file') <div class="wl-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="wl-field full">
                        <label class="wl-label" for="reference_link">Tautan lampiran <small>(opsional, referensi tugas)</small></label>
                        <input type="url" id="reference_link" name="reference_link" class="wl-input" maxlength="500" placeholder="https://..."
                            value="{{ old('reference_link', optional($task)->reference_link) }}">
                        @error('reference_link') <div class="wl-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="wl-field full">
                        <label class="wl-label">Peserta wajib melampirkan bukti pengerjaan? <span class="req">*</span></label>
                        @php($req = (string) old('require_attachment', optional($task)->require_attachment ? '1' : '0'))
                        <div class="wl-radio-group">
                            <label class="wl-radio"><input type="radio" name="require_attachment" value="1" {{ $req === '1' ? 'checked' : '' }}> Wajib ada lampiran</label>
                            <label class="wl-radio"><input type="radio" name="require_attachment" value="0" {{ $req === '0' ? 'checked' : '' }}> Lampiran opsional</label>
                        </div>
                        @error('require_attachment') <div class="wl-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr class="wl-divider">

                    <div class="wl-section-head">
                        <div>
                            <h3 class="wl-section-title" style="margin-bottom: 4px;">Pilih peserta magang <span class="req" style="color: var(--cs-rose)">*</span></h3>
                            <div class="wl-sub">{{ $isEdit ? 'Centang untuk menambah peserta, hapus centang untuk mengeluarkan peserta dari tugas ini.' : 'Klik kartu untuk memilih peserta. Pekerjaan akan dibuat untuk setiap peserta terpilih.' }}</div>
                        </div>
                        <span class="wl-pill-count" data-wl-count>0 dipilih</span>
                    </div>

                    @if ($participants->isEmpty())
                        <div class="wl-empty">Tidak ada peserta magang{{ $isMentor ? ' di divisi Anda' : '' }}.</div>
                    @else
                        <div class="wl-field">
                            <input type="text" class="wl-input" data-wl-psearch placeholder="Cari nama, divisi, jurusan, atau kampus...">
                        </div>
                        <div class="wl-pgrid">
                            @php($picked = array_map('intval', (array) old('participants', $currentIds ?? [])))
                            @foreach ($participants as $p)
                                @php($on = in_array($p->id, $picked, true))
                                <label class="wl-pcard {{ $on ? 'is-selected' : '' }}"
                                    data-search="{{ strtolower($p->name . ' ' . optional($p->division)->name . ' ' . $p->major . ' ' . $p->institution . ' ' . $p->email) }}">
                                    <input type="checkbox" name="participants[]" value="{{ $p->id }}" {{ $on ? 'checked' : '' }}>
                                    <span class="wl-chip wl-c{{ ($p->division_id ?: 0) % 5 }}">{{ optional($p->division)->name ?: '-' }}</span>
                                    <span class="wl-avatar">{{ \App\Models\WorkTask::initials($p->name) }}</span>
                                    <div class="wl-pname">{{ $p->name }}</div>
                                    <div class="wl-pline">{{ $p->major ?: '-' }}</div>
                                    <div class="wl-pline">{{ $p->email }}</div>
                                    <div class="wl-pline">{{ $p->institution ?: '-' }}</div>
                                    <div class="wl-pperiod">{{ \App\Models\WorkTask::period($p) }}</div>
                                    <span class="wl-pbadge">Peserta magang</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('participants') <div class="wl-error">{{ $message }}</div> @enderror
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="{{ $isEdit ? route('work-logs.show', $task) : route('work-logs.index') }}" class="wl-btn">Batal</a>
                <button type="submit" class="wl-btn wl-btn--primary">{{ $isEdit ? 'Simpan perubahan' : 'Buat tugas' }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=4"></script>
@endsection
