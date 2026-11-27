@extends('layouts.master')

@php($isEdit = !is_null($task))
@php($children = $isEdit ? (bool) $task->has_children : (string) old('has_children', '0') === '1')
@php($title = $isEdit ? 'Edit tugas harian' : 'Tambah tugas harian')

@section('css')
    <link href="{{ URL::asset('css/work-log.css') }}?v=19" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">{{ $title }}</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('work-logs.index', ['type' => 'daily']) }}">Log Pekerjaan</a></li>
            <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="wl">
        <div class="wl-head">
            <div>
                <h2 class="wl-title">{{ $title }}</h2>
                <p class="wl-sub">Isian sama seperti peserta. Matikan "Tugas dengan turunan" untuk mencatat pekerjaan atas nama peserta (langsung selesai).</p>
            </div>
        </div>

        <form method="POST" data-wl-picker action="{{ $isEdit ? route('work-logs.update', $task) : route('work-logs.store') }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="type" value="daily">

            <div class="wl-card">
                @include('includes.work-daily-fields', [
                    'task' => $task, 'isEdit' => $isEdit, 'categoryGroups' => $categoryGroups, 'categoryRequired' => true, 'startLimit' => null,
                ])

                {{-- Peserta: kartu. Pencatatan = pilih satu; dengan turunan = boleh banyak. --}}
                <hr class="wl-divider">
                @if ($isEdit && ! $children)
                    <div class="wl-field">
                        <label class="wl-label">Peserta</label>
                        <div>{{ $task->assignees->map(function ($a) { return optional($a->user)->name; })->filter()->implode(', ') }}</div>
                    </div>
                @else
                    <div class="wl-section-head">
                        <div>
                            <h3 class="wl-section-title" style="margin-bottom: 4px;">Pilih peserta magang <span class="req" style="color: var(--cs-rose)">*</span></h3>
                            <div class="wl-sub">
                                <span data-record-only @if ($children) hidden @endif>Pilih satu peserta. Pekerjaan dicatat atas namanya dan langsung berstatus selesai.</span>
                                <span data-children-only @if (! $children) hidden @endif>{{ $isEdit ? 'Centang untuk menambah peserta, hapus centang untuk mengeluarkan. Catatan progres (turunan) peserta yang dikeluarkan tetap tersimpan.' : 'Boleh lebih dari satu. Tiap peserta menambahkan turunannya sendiri, Total selesai dijumlahkan.' }}</span>
                            </div>
                        </div>
                        <span class="wl-pill-count" data-wl-count>0 dipilih</span>
                    </div>
                    @if ($participants->isEmpty())
                        <div class="wl-empty">Tidak ada peserta magang{{ $isMentor ? ' di divisi Anda' : '' }}.</div>
                    @else
                        <div class="wl-field">
                            <input type="text" class="wl-input" data-wl-psearch placeholder="Cari nama, divisi, jurusan, atau kampus...">
                        </div>
                        <div class="pc-pick-grid" data-pc-pick data-mode-pick data-single="{{ $children ? '0' : '1' }}" data-logo="{{ asset('assets/images/logo-plnsc.png') }}">
                            @php($picked = array_map('intval', (array) old('participants', $currentIds ?? [])))
                            @foreach ($participants as $p)
                                @php($on = in_array($p->id, $picked, true))
                                <label class="pc-pick {{ $on ? 'is-selected' : '' }}"
                                    data-person="{{ json_encode($p->directoryCard(true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                                    <input type="checkbox" name="participants[]" value="{{ $p->id }}" {{ $on ? 'checked' : '' }}>
                                    <span class="pc-pick-card"><span class="pc-pick-fallback">{{ $p->name }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('participants') <div class="wl-error">{{ $message }}</div> @enderror
                    @error('participants.*') <div class="wl-error">{{ $message }}</div> @enderror
                @endif

                {{-- Dengan turunan: bukti --}}
                @if (! $isEdit || $children)
                    <div data-children-only @if (! $children) hidden @endif>
                        <hr class="wl-divider">
                        <div class="wl-field">
                            <label class="wl-label">Peserta wajib melampirkan bukti saat mengajukan selesai? <span class="req">*</span></label>
                            @php($req = (string) old('require_attachment', optional($task)->require_attachment ? '1' : '0'))
                            <div class="wl-radio-group">
                                <label class="wl-radio"><input type="radio" name="require_attachment" value="1" {{ $req === '1' ? 'checked' : '' }}> Wajib ada lampiran</label>
                                <label class="wl-radio"><input type="radio" name="require_attachment" value="0" {{ $req === '0' ? 'checked' : '' }}> Lampiran opsional</label>
                            </div>
                            @error('require_attachment') <div class="wl-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                @endif
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="{{ $isEdit ? route('work-logs.show', $task) : route('work-logs.index', ['type' => 'daily']) }}" class="wl-btn">Batal</a>
                <button type="submit" class="wl-btn wl-btn--primary"
                    @unless ($isEdit) data-submit-label data-label-children="Buat tugas" data-label-record="Catat pekerjaan" @endunless>{{ $isEdit ? 'Simpan perubahan' : ($children ? 'Buat tugas' : 'Catat pekerjaan') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('js/work-log.js') }}?v=13"></script>
@endsection
