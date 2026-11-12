@extends('layouts.master')

@php($isEdit = !is_null($task))

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=11" rel="stylesheet" type="text/css" />
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

                    {{-- Blok PHP biasa (bukan direktif multi-baris) agar tidak bentrok dengan direktif php satu baris di file ini. --}}
                    <?php
                        $oldRefLinks = [];
                        foreach ((array) old('reference_links', []) as $i => $u) {
                            if ($u) { $oldRefLinks[] = ['url' => $u, 'label' => old('reference_link_labels.' . $i)]; }
                        }
                        $existingRefs = [];
                        if ($isEdit) {
                            foreach ($task->references as $ref) {
                                $existingRefs[] = [
                                    'id' => $ref->id,
                                    'type' => $ref->type,
                                    'name' => $ref->name,
                                    'url' => $ref->isLink() ? $ref->url : route('work-logs.file.reference', $ref),
                                ];
                            }
                        }
                    ?>
                    <div class="wl-field" data-wl-attach data-wl-attach-modal="#wl-link-modal"
                        data-wl-link-name="reference_links" data-wl-label-name="reference_link_labels" data-wl-remove-name="remove_references"
                        data-wl-links="{{ json_encode($oldRefLinks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                        data-wl-existing="{{ json_encode($existingRefs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                        <label class="wl-label">Lampiran referensi tugas <small>(opsional)</small></label>
                        <div class="wl-attach">
                            <div class="wl-menu-wrap">
                                <button type="button" class="wl-attach-btn" data-wl-menu>
                                    <i class="mdi mdi-paperclip"></i> Lampirkan <i class="mdi mdi-chevron-down"></i>
                                </button>
                                <div class="wl-menu wl-menu--left">
                                    <button type="button" data-wl-open="#wl-link-modal"><i class="mdi mdi-link-variant"></i> Tautan</button>
                                    <hr>
                                    <button type="button" data-wl-upload="#reference_files"><i class="mdi mdi-paperclip"></i> Unggah dari perangkat</button>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="reference_files" name="reference_files[]" multiple hidden data-wl-attach-files>
                        <div data-wl-attach-out hidden></div>
                        <div class="wl-attach-list" data-wl-attach-list></div>
                        <div class="wl-hint">Boleh lebih dari satu file (maksimal 10 MB per file) dan lebih dari satu tautan sebagai referensi tugas.
                            @if ($errors->any()) File perlu dipilih ulang. @endif</div>
                        @foreach (collect($errors->get('reference_files'))->merge(collect($errors->get('reference_files.*'))->flatten())->merge($errors->get('reference_links'))->merge(collect($errors->get('reference_links.*'))->flatten())->unique() as $msg)
                            <div class="wl-error">{{ $msg }}</div>
                        @endforeach
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
                        <div class="pc-pick-grid" data-pc-pick data-logo="{{ asset('assets/images/logo-plnsc.png') }}">
                            @php($picked = array_map('intval', (array) old('participants', $currentIds ?? [])))
                            @foreach ($participants as $p)
                                @php($on = in_array($p->id, $picked, true))
                                {{-- Kartu people-card untuk mentor/admin: data lengkap (directoryCard(true)), tidak mengikuti centang pemilik kartu. --}}
                                <label class="pc-pick {{ $on ? 'is-selected' : '' }}"
                                    data-person="{{ json_encode($p->directoryCard(true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                                    <input type="checkbox" name="participants[]" value="{{ $p->id }}" {{ $on ? 'checked' : '' }}>
                                    <span class="pc-pick-card"><span class="pc-pick-fallback">{{ $p->name }}</span></span>
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

    {{-- Modal tautan (di luar form); tiap tautan ditambahkan ke daftar dan dikirim sebagai reference_links[]. --}}
    <div class="wl-modal wl" id="wl-link-modal">
        <div class="wl-modal-box" style="width: min(480px, 100%);">
            <div class="wl-modal-head">
                <h5>Tambah tautan</h5>
                <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
            </div>
            <div class="wl-modal-body">
                <div class="wl-field">
                    <label class="wl-label" for="wl-modal-url">Alamat tautan <span class="req">*</span></label>
                    <input type="url" id="wl-modal-url" class="wl-input" maxlength="500" placeholder="https://..." data-wl-modal-url>
                </div>
                <div class="wl-field">
                    <label class="wl-label" for="wl-modal-label">Nama tampilan <small>(opsional)</small></label>
                    <input type="text" id="wl-modal-label" class="wl-input" maxlength="120" data-wl-modal-label>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="wl-btn" data-wl-close>Batal</button>
                    <button type="button" class="wl-btn wl-btn--primary" data-wl-modal-add>Tambahkan</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=10"></script>
@endsection
