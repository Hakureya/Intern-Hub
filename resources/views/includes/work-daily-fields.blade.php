{{--
    Isian tugas harian (dipakai form peserta dan form mentor).
    Dasar: judul, kategori, deskripsi, satuan, tenggat (opsional).
    Tombol "Tugas dengan turunan":
      - AKTIF  : tanpa tanggal mulai/selesai/jumlah output; tanggal pengerjaan + jumlah output diisi per turunan di halaman detail.
      - MATI   : pencatatan biasa dengan tanggal mulai, tanggal selesai, dan jumlah output.
    Param: $task (nullable), $isEdit, $categoryGroups (grup => daftar nama), $categoryRequired (bool), $startLimit (Carbon|null).
    Blok khusus mode memakai atribut data-children-only / data-record-only (bisa dipakai juga di luar partial ini).
--}}
<?php
    $childrenOn = $isEdit ? (bool) $task->has_children : (string) old('has_children', '0') === '1';
    $startLimit = $startLimit ?? null;
    $defaultStart = $isEdit && ! $childrenOn ? $task->workedDate()->toDateString() : now()->toDateString();
    $defaultEnd = $isEdit && ! $childrenOn ? optional($task->endDate() ?: $task->workedDate())->toDateString() : now()->toDateString();
?>

<div class="wl-field">
    <label class="wl-label" for="title">Judul tugas <span class="req">*</span></label>
    <input type="text" id="title" name="title" class="wl-input" maxlength="150" required
        value="{{ old('title', optional($task)->title) }}" placeholder="Contoh: Mengemas bantex ke dalam boks arsip">
    @error('title') <div class="wl-error">{{ $message }}</div> @enderror
</div>

<div class="wl-field">
    <label class="wl-label" for="category">Kategori aktivitas @if ($categoryRequired) <span class="req">*</span> @else <small>(opsional)</small> @endif</label>
    <select id="category" name="category" class="wl-select" @if ($categoryRequired) required @endif>
        <option value="">- Pilih kategori -</option>
        @foreach ($categoryGroups as $group => $names)
            @if (count($categoryGroups) > 1) <optgroup label="{{ $group }}"> @endif
            @foreach ($names as $name)
                <option value="{{ $name }}" {{ old('category', optional($task)->category) === $name ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
            @if (count($categoryGroups) > 1) </optgroup> @endif
        @endforeach
    </select>
    @error('category') <div class="wl-error">{{ $message }}</div> @enderror
</div>

<div class="wl-field">
    <label class="wl-label" for="description">Deskripsi / rincian pekerjaan <span class="req">*</span></label>
    <textarea id="description" name="description" class="wl-textarea" maxlength="5000" required
        placeholder="Apa yang dikerjakan, sumber datanya, atau hasil penyusunannya.">{{ old('description', optional($task)->description) }}</textarea>
    @error('description') <div class="wl-error">{{ $message }}</div> @enderror
</div>

<div class="wl-form-grid">
    <div class="wl-field">
        <label class="wl-label" for="quantity_unit">Satuan output <span class="req" data-children-only @if (! $childrenOn) hidden @endif>*</span></label>
        <input type="text" id="quantity_unit" name="quantity_unit" class="wl-input" maxlength="40" list="wl-units" data-req-children
            @if ($childrenOn) required @endif
            placeholder="Dokumen / Baris Data / Bantex" value="{{ old('quantity_unit', optional($task)->quantity_unit) }}">
        <datalist id="wl-units"><option value="Dokumen"><option value="Baris Data"><option value="Invoice"><option value="Bantex"><option value="Boks"><option value="PO"><option value="Item"></datalist>
        @error('quantity_unit') <div class="wl-error">{{ $message }}</div> @enderror
    </div>
    <div class="wl-field">
        <label class="wl-label" for="deadline">Tenggat <small>(opsional)</small></label>
        <input type="date" id="deadline" name="deadline" class="wl-input"
            min="{{ $isEdit ? $task->workedDate()->toDateString() : now()->toDateString() }}"
            value="{{ old('deadline', optional(optional($task)->deadline)->toDateString()) }}">
        @error('deadline') <div class="wl-error">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Tombol tugas dengan turunan --}}
@if ($isEdit)
    <div class="wl-note wl-note--blue" style="margin-bottom: 14px;">
        <b>{{ $childrenOn ? 'Tugas dengan turunan' : 'Tugas pencatatan' }}</b> &mdash; jenis tugas tidak dapat diubah setelah dibuat.
    </div>
@else
    <input type="hidden" name="has_children" value="{{ $childrenOn ? '1' : '0' }}">
    <button type="button" class="wl-toggle {{ $childrenOn ? 'is-on' : '' }}" data-children-toggle aria-pressed="{{ $childrenOn ? 'true' : 'false' }}">
        <span class="wl-toggle-box"><i class="mdi mdi-check"></i></span>
        <span>
            <b>Tugas dengan turunan</b>
            <small>Aktifkan bila pekerjaan yang sama dikerjakan beberapa hari. Tanggal pengerjaan dan jumlah output diisi per hari di halaman detail.</small>
        </span>
    </button>
@endif

{{-- Pencatatan: tanggal mulai, tanggal selesai, jumlah output --}}
@if (! $isEdit || ! $childrenOn)
    <div data-record-only @if ($childrenOn) hidden @endif>
        <div class="wl-form-grid">
            <div class="wl-field">
                <label class="wl-label" for="start_date">Tanggal mulai <span class="req">*</span></label>
                <input type="date" id="start_date" name="start_date" class="wl-input" required data-wl-date-start
                    max="{{ now()->toDateString() }}"
                    @if ($startLimit && $startLimit->lte(now())) min="{{ $startLimit->toDateString() }}" @endif
                    value="{{ old('start_date', $defaultStart) }}">
                @error('start_date') <div class="wl-error">{{ $message }}</div> @enderror
            </div>
            <div class="wl-field">
                <label class="wl-label" for="end_date">Tanggal selesai <span class="req">*</span></label>
                <input type="date" id="end_date" name="end_date" class="wl-input" required data-wl-date-end
                    max="{{ now()->toDateString() }}" min="{{ old('start_date', $defaultStart) }}"
                    value="{{ old('end_date', $defaultEnd) }}">
                @error('end_date') <div class="wl-error">{{ $message }}</div> @enderror
            </div>
            <div class="wl-field">
                <label class="wl-label" for="quantity">Jumlah output <small>(disarankan)</small></label>
                <input type="number" id="quantity" name="quantity" class="wl-input" min="0" max="1000000" step="1" placeholder="Contoh: 150"
                    value="{{ old('quantity', optional($task)->quantity) }}">
                @error('quantity') <div class="wl-error">{{ $message }}</div> @enderror
            </div>
            <div class="wl-hint full" style="grid-column: 1 / -1; margin-top: -6px;">Jika hanya satu hari, isi kedua tanggal sama. Satuan jumlah output memakai isian "Satuan output" di atas.</div>
        </div>
        <div class="wl-field">
            <label class="wl-label">Status pengerjaan <span class="req">*</span></label>
            <div class="wl-radio-group">
                @foreach (\App\Models\WorkTask::WORK_STATUSES as $key => $label)
                    <label class="wl-radio"><input type="radio" name="work_status" value="{{ $key }}" required
                        {{ old('work_status', optional($task)->work_status ?: 'done') === $key ? 'checked' : '' }}> {{ $label }}</label>
                @endforeach
            </div>
            @error('work_status') <div class="wl-error">{{ $message }}</div> @enderror
        </div>
    </div>
@endif

{{-- Dengan turunan: target total opsional; tanggal & jumlah diisi di turunan --}}
@if (! $isEdit || $childrenOn)
    <div data-children-only @if (! $childrenOn) hidden @endif>
        <div class="wl-field">
            <label class="wl-label" for="target_quantity">Target jumlah total <small>(opsional)</small></label>
            <input type="number" id="target_quantity" name="target_quantity" class="wl-input" min="1" max="1000000" step="1"
                placeholder="Contoh: 500" value="{{ old('target_quantity', optional($task)->target_quantity) }}" style="max-width: 240px;">
            <div class="wl-hint">Bila diisi, bar progres tampil di daftar dan detail tugas. Tanpa target, hanya total output yang ditampilkan.</div>
            @error('target_quantity') <div class="wl-error">{{ $message }}</div> @enderror
        </div>
        @unless ($isEdit)
            <div class="wl-note wl-note--blue" style="margin-bottom: 12px;">Setelah tugas dibuat, tambahkan turunan (tanggal pengerjaan + jumlah output) dari halaman detail. Bisa ditambah lagi untuk hari berikutnya.</div>
        @endunless
    </div>
@endif

@unless ($isEdit)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('[data-children-toggle]');
        var input = document.querySelector('input[name="has_children"]');
        if (!btn || !input) { return; }
        function show(el, on) {
            el.hidden = !on;
            el.querySelectorAll('input, select, textarea').forEach(function (i) { i.disabled = !on; });
        }
        function apply(on) {
            btn.classList.toggle('is-on', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            input.value = on ? '1' : '0';
            document.querySelectorAll('[data-children-only]').forEach(function (el) {
                if (el.tagName === 'SPAN') { el.hidden = !on; } else { show(el, on); }
            });
            document.querySelectorAll('[data-record-only]').forEach(function (el) { show(el, !on); });
            document.querySelectorAll('[data-pc-pick][data-mode-pick]').forEach(function (g) {
                g.setAttribute('data-single', on ? '0' : '1');
                var checked = g.querySelectorAll('input:checked');
                for (var k = 1; !on && k < checked.length; k++) { // pencatatan: hanya satu peserta
                    checked[k].checked = false;
                    checked[k].closest('.pc-pick').classList.remove('is-selected');
                }
                var count = document.querySelector('[data-wl-count]');
                if (count) { count.textContent = g.querySelectorAll('input:checked').length + ' dipilih'; }
            });
            document.querySelectorAll('[data-req-children]').forEach(function (i) { i.required = on; });
            document.querySelectorAll('[data-submit-label]').forEach(function (b) { b.textContent = on ? b.getAttribute('data-label-children') : b.getAttribute('data-label-record'); });
        }
        btn.addEventListener('click', function () { apply(input.value !== '1'); });
        apply(input.value === '1');
    });
</script>
@endunless
