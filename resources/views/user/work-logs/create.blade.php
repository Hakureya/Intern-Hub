@extends('layouts.master-blank')

@php($isEdit = !is_null($task))

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/work-log.css') }}?v=11" rel="stylesheet" type="text/css" />
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
            @if ($isEdit) data-confirm="Menyimpan perubahan akan mengembalikan status menjadi Belum. Lampiranmu tetap tersimpan." data-confirm-title="Simpan perubahan?" data-confirm-ok="Ya, simpan" data-confirm-level="warning" data-confirm-tone="normal" @endif>
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

                @if (!$isEdit || $task->is_self)
                    @php($startLimit = ($me = auth()->user())->internship_start_date ? \Carbon\Carbon::parse($me->internship_start_date) : null)
                    <div class="wl-field" style="max-width: 260px;">
                        <label class="wl-label" for="worked_on">Dikerjakan pada <span class="req">*</span></label>
                        <input type="date" id="worked_on" name="worked_on" class="wl-input" required
                            max="{{ now()->toDateString() }}"
                            @if ($startLimit && $startLimit->lte(now())) min="{{ $startLimit->toDateString() }}" @endif
                            value="{{ old('worked_on', optional(optional($task)->worked_on)->toDateString() ?: now()->toDateString()) }}">
                        <div class="wl-hint">Pilih tanggal saat pekerjaan ini dikerjakan. Pekerjaan beberapa hari lalu juga bisa dicatat.</div>
                        @error('worked_on') <div class="wl-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if ($isEdit)
                    <div class="wl-note">Setiap perubahan mengembalikan status menjadi <b>Belum</b>. Lampiran yang sudah ada tetap tersimpan;
                        kelola lampiran dan ajukan tandai selesai lagi dari halaman detail.</div>
                @else
                    {{-- Blok PHP biasa (bukan direktif multi-baris) agar tidak bentrok dengan direktif php satu baris di file ini. --}}
                    <?php
                        $oldLinks = [];
                        foreach ((array) old('link_urls', []) as $i => $u) {
                            if ($u) { $oldLinks[] = ['url' => $u, 'label' => old('link_labels.' . $i)]; }
                        }
                    ?>
                    <div class="wl-field" data-wl-attach data-wl-attach-modal="#wl-link-modal"
                        data-wl-link-name="link_urls" data-wl-label-name="link_labels"
                        data-wl-links="{{ json_encode($oldLinks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                        <label class="wl-label">Lampiran bukti pengerjaan <small>(opsional)</small></label>
                        <div class="wl-attach">
                            <div class="wl-menu-wrap">
                                <button type="button" class="wl-attach-btn" data-wl-menu>
                                    <i class="mdi mdi-paperclip"></i> Lampirkan <i class="mdi mdi-chevron-down"></i>
                                </button>
                                <div class="wl-menu wl-menu--left">
                                    <button type="button" data-wl-open="#wl-link-modal"><i class="mdi mdi-link-variant"></i> Tautan</button>
                                    <hr>
                                    <button type="button" data-wl-upload="#files"><i class="mdi mdi-paperclip"></i> Unggah dari perangkat</button>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="files" name="files[]" multiple hidden data-wl-attach-files>
                        <div data-wl-attach-out hidden></div>
                        <div class="wl-attach-list" data-wl-attach-list></div>
                        <div class="wl-hint">Boleh lebih dari satu file (jpg, png, pdf, doc, xls, ppt, zip, maksimal 10 MB per file) dan lebih dari satu tautan.
                            @if ($errors->any()) File perlu dipilih ulang. @endif</div>
                        @if ($errors->has('files') || $errors->has('files.*') || $errors->has('link_url') || $errors->has('link_urls') || $errors->has('link_urls.*'))
                            @foreach (collect($errors->get('files'))->merge(collect($errors->get('files.*'))->flatten())->merge($errors->get('link_url'))->merge($errors->get('link_urls'))->merge(collect($errors->get('link_urls.*'))->flatten())->unique() as $msg)
                                <div class="wl-error">{{ $msg }}</div>
                            @endforeach
                        @endif
                    </div>
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
                        <div class="pc-pick-grid" data-pc-pick data-logo="{{ asset('assets/images/logo-plnsc.png') }}">
                            @php($picked = array_map('intval', (array) old('partners', [])))
                            @foreach ($partners as $p)
                                @php($on = in_array($p->id, $picked, true))
                                {{-- Kartu dari User::directoryCard(): hanya isian yang diizinkan pemiliknya yang ikut dikirim. --}}
                                <label class="pc-pick {{ $on ? 'is-selected' : '' }}"
                                    data-person="{{ json_encode($p->directoryCard(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                                    <input type="checkbox" name="partners[]" value="{{ $p->id }}" {{ $on ? 'checked' : '' }}>
                                    <span class="pc-pick-card"><span class="pc-pick-fallback">{{ $p->name }}</span></span>
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

@unless ($isEdit)
    {{-- Modal tautan (di luar form agar tidak ada form bersarang); nilainya disalin ke input tersembunyi lalu dikirim bersama form. --}}
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
@endunless
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=10"></script>
@endsection
