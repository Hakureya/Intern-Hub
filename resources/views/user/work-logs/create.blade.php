@extends('layouts.master-blank')

@php($isEdit = !is_null($task))

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
                <h2 class="wl-title">{{ $isEdit ? 'Edit tugas' : 'Tambah tugas yang dikerjakan' }}</h2>
                <p class="wl-sub">
                    {{ $isEdit ? 'Tugas hanya bisa diedit sebelum tenggat.' : 'Catat pekerjaan yang sudah kamu kerjakan. Mentor akan memeriksanya.' }}
                </p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" data-wl-picker
            action="{{ $isEdit ? route('user.work-logs.update', $task) : route('user.work-logs.store') }}"
            @if ($isEdit) onsubmit="return confirm('Menyimpan perubahan akan mengembalikan status menjadi Belum. Lanjutkan?')" @endif>
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div class="wl-card">
                <div class="wl-field">
                    <label class="wl-label" for="title">Judul tugas <span class="req">*</span></label>
                    <input type="text" id="title" name="title" class="wl-input" maxlength="150" required
                        value="{{ old('title', optional($task)->title) }}" placeholder="Contoh: Rekap data inventaris">
                    @error('title') <div class="wl-error">{{ $message }}</div> @enderror
                </div>

                <div class="wl-field">
                    <label class="wl-label" for="description">Deskripsi <span class="req">*</span></label>
                    <textarea id="description" name="description" class="wl-textarea" maxlength="5000" required
                        placeholder="Jelaskan pekerjaan yang kamu lakukan.">{{ old('description', optional($task)->description) }}</textarea>
                    @error('description') <div class="wl-error">{{ $message }}</div> @enderror
                </div>

                @if ($isEdit)
                    <div class="wl-note">Setiap perubahan mengembalikan status menjadi <b>Belum</b>. Lampiran yang sudah ada tetap tersimpan;
                        kelola lampiran dan ajukan tandai selesai lagi dari halaman detail.</div>
                @else
                    <div class="wl-field">
                        <label class="wl-label" for="files">Lampiran bukti pengerjaan <span class="req">*</span></label>
                        <input type="file" id="files" name="files[]" class="wl-input" multiple>
                        <div class="wl-hint">Boleh lebih dari satu file (jpg, png, pdf, doc, xls, ppt, zip), maksimal 10 MB per file.</div>
                    </div>
                    <div class="wl-form-grid">
                        <div class="wl-field">
                            <label class="wl-label" for="link_url">Tautan bukti <small>(opsional)</small></label>
                            <input type="url" id="link_url" name="link_url" class="wl-input" maxlength="500" placeholder="https://..." value="{{ old('link_url') }}">
                        </div>
                        <div class="wl-field">
                            <label class="wl-label" for="link_label">Nama tautan <small>(opsional)</small></label>
                            <input type="text" id="link_label" name="link_label" class="wl-input" maxlength="120" value="{{ old('link_label') }}">
                        </div>
                    </div>
                    @if ($errors->has('files') || $errors->has('files.*') || $errors->has('link_url'))
                        @foreach (collect($errors->get('files'))->merge(collect($errors->get('files.*'))->flatten())->merge($errors->get('link_url'))->unique() as $msg)
                            <div class="wl-error">{{ $msg }}</div>
                        @endforeach
                    @endif
                @endif

                @unless ($isEdit)
                    <hr class="wl-divider">
                    <div class="wl-section-head">
                        <div>
                            <h3 class="wl-section-title" style="margin-bottom: 4px;">Dikerjakan bersama <small style="font-weight: 500; color: var(--cs-soft);">(opsional)</small></h3>
                            <div class="wl-sub">Pilih teman satu divisi. Mereka akan menerima permintaan untuk mengonfirmasi.</div>
                        </div>
                        <span class="wl-pill-count" data-wl-count>0 dipilih</span>
                    </div>

                    @if ($partners->isEmpty())
                        <div class="wl-empty">Belum ada teman satu divisi yang bisa dipilih.</div>
                    @else
                        <div class="wl-pgrid">
                            @php($picked = array_map('intval', (array) old('partners', [])))
                            @foreach ($partners as $p)
                                @php($on = in_array($p->id, $picked, true))
                                <label class="wl-pcard {{ $on ? 'is-selected' : '' }}">
                                    <input type="checkbox" name="partners[]" value="{{ $p->id }}" {{ $on ? 'checked' : '' }}>
                                    <span class="wl-chip wl-c{{ ($p->division_id ?: 0) % 5 }}">{{ optional($p->division)->name ?: '-' }}</span>
                                    <span class="wl-avatar">{{ \App\Models\WorkTask::initials($p->name) }}</span>
                                    <div class="wl-pname">{{ $p->name }}</div>
                                    <div class="wl-pline">{{ optional($p->division)->name ?: '-' }}</div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('partners') <div class="wl-error">{{ $message }}</div> @enderror
                @endunless
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="{{ $isEdit ? route('user.work-logs.show', $task) : route('user.work-logs.index') }}" class="wl-btn">Batal</a>
                <button type="submit" class="wl-btn wl-btn--primary">{{ $isEdit ? 'Simpan perubahan' : 'Kirim untuk disetujui' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=4"></script>
@endsection
