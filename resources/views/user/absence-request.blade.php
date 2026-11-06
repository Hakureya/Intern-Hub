@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=1" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @php
        $selectedType = $selectedType ?? 'izin_sakit';
        $absenceActiveTab = $selectedType === 'lupa_absensi' ? 'absence_lupa' : 'absence_izin';
    @endphp
    @include('layouts.user-navbar', ['active' => $absenceActiveTab])

    <div class="absence-page">
        <div class="absence-shell">
            <div class="absence-card">
                <div class="absence-title">Pengajuan Ketidakhadiran / Izin</div>
                <div class="mb-3 d-flex flex-wrap" style="gap:8px;">
                    <a href="{{ route('user.absence.index', ['type' => 'izin_sakit']) }}"
                        class="btn btn-sm {{ $selectedType === 'izin_sakit' ? 'btn-primary' : 'btn-light' }}">
                        Izin &amp; Sakit
                    </a>
                    <a href="{{ route('user.absence.index', ['type' => 'lupa_absensi']) }}"
                        class="btn btn-sm {{ $selectedType === 'lupa_absensi' ? 'btn-primary' : 'btn-light' }}">
                        Lupa Absensi
                    </a>
                </div>
                @if (!$user)
                    <div class="alert alert-warning mb-0">Data peserta magang belum tersedia. Lengkapi profil terlebih
                        dahulu.</div>
                @else
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <form method="POST" action="{{ route('user.absence.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="menu_type" value="{{ $selectedType }}">
                        <div class="form-row-wrap">
                            @if ($selectedType === 'lupa_absensi')
                                <div class="form-group">
                                    <label>Tanggal Lupa Absensi</label>
                                    <input type="date" name="absence_date" class="form-control"
                                        value="{{ old('absence_date', now()->toDateString()) }}" required>
                                </div>
                            @else
                                <div class="form-group">
                                    <label for="absence_date">Tanggal Mulai</label>
                                    <input type="date" id="absence_date" name="absence_date" class="form-control"
                                        value="{{ old('absence_date', now()->toDateString()) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="absence_end_date">Tanggal Selesai</label>
                                    <input type="date" id="absence_end_date" name="absence_end_date"
                                        class="form-control"
                                        value="{{ old('absence_end_date', old('absence_date', now()->toDateString())) }}"
                                        required>
                                    <small id="absence-days-hint" class="text-muted">Isi tanggal yang sama bila hanya 1
                                        hari (maksimal {{ \App\Models\AbsenceRequest::MAX_RANGE_DAYS }} hari).</small>
                                </div>
                            @endif
                            <div class="form-group">
                                <label>Jenis Tidak Masuk</label>
                                @if ($selectedType === 'lupa_absensi')
                                    <input type="hidden" name="absence_type" value="lupa_absensi">
                                    <input type="text" class="form-control" value="Lupa Absensi" disabled>
                                @else
                                    <select name="absence_type" class="form-control" required>
                                        <option value="izin" {{ old('absence_type') === 'izin' ? 'selected' : '' }}>Izin
                                        </option>
                                        <option value="sakit" {{ old('absence_type') === 'sakit' ? 'selected' : '' }}>Sakit
                                        </option>
                                    </select>
                                @endif
                            </div>
                            @if ($selectedType === 'lupa_absensi')
                                <div class="form-group">
                                    <label>Jam Masuk Koreksi (opsional)</label>
                                    <input type="time" name="correction_time_in" class="form-control"
                                        value="{{ old('correction_time_in') }}">
                                </div>
                                <div class="form-group">
                                    <label>Jam Pulang Koreksi (opsional)</label>
                                    <input type="time" name="correction_time_out" class="form-control"
                                        value="{{ old('correction_time_out') }}">
                                    <small class="text-muted">Isi minimal salah satu jam (masuk/pulang).</small>
                                </div>
                            @endif
                            <div class="form-group">
                                <label>Dokumen Pendukung (Opsional)</label>
                                <input type="file" name="document" class="form-control"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small class="text-muted">Format: pdf/jpg/png/doc/docx, maksimal 5MB.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alasan</label>
                            <textarea name="reason" rows="4" class="form-control" placeholder="Tuliskan alasan izin/ketidakhadiran..."
                                required>{{ old('reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Kirim Pengajuan</button>
                    </form>
                @endif
            </div>

            <div class="absence-card">
                <div class="absence-title">Riwayat Pengajuan</div>
                @if ($requests->isEmpty())
                    <div class="text-muted">Belum ada pengajuan.</div>
                @else
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Jam Koreksi</th>
                                    <th>Alasan</th>
                                    <th>Dokumen</th>
                                    <th>Status</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $item)
                                    @php
                                        $status = strtolower((string) $item->status);
                                        $pillClass = 'status-pending';
                                        if ($status === 'approved') {
                                            $pillClass = 'status-approved';
                                        }
                                        if ($status === 'rejected') {
                                            $pillClass = 'status-rejected';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $item->date_range_label }}
                                            @if ($item->is_range)
                                                <small class="text-muted d-block">{{ $item->days_count }} hari</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->absence_type === 'lupa_absensi' ? 'Lupa Absensi' : ucfirst($item->absence_type ?: 'izin') }}
                                        </td>
                                        <td>
                                            {{ $item->correction_time_in ? \Carbon\Carbon::parse($item->correction_time_in)->format('H:i') : '-' }}
                                            -
                                            {{ $item->correction_time_out ? \Carbon\Carbon::parse($item->correction_time_out)->format('H:i') : '-' }}
                                        </td>
                                        <td>{{ $item->reason }}</td>
                                        <td>
                                            @if ($item->document_path)
                                                <a href="{{ route('user.absence.view', $item->id) }}" target="_blank"
                                                    rel="noopener">Lihat</a>
                                                |
                                                <a href="{{ route('user.absence.download', $item->id) }}">Unduh</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $pillClass }}">{{ strtoupper($status) }}</span>
                                        </td>
                                        <td>{{ $item->admin_note ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-md-none">
                        @foreach ($requests as $item)
                            @php
                                $status = strtolower((string) $item->status);
                                $pillClass = 'status-pending';
                                if ($status === 'approved') {
                                    $pillClass = 'status-approved';
                                }
                                if ($status === 'rejected') {
                                    $pillClass = 'status-rejected';
                                }
                            @endphp
                            <div class="border rounded p-2 mb-2 bg-white">
                                <div class="font-weight-bold">
                                    {{ $item->date_range_label }}
                                    @if ($item->is_range)
                                        <small class="text-muted">({{ $item->days_count }} hari)</small>
                                    @endif
                                </div>
                                <div class="small text-muted mb-1">
                                    {{ $item->absence_type === 'lupa_absensi' ? 'Lupa Absensi' : ucfirst($item->absence_type ?: 'izin') }}
                                </div>
                                <div class="small mb-1">
                                    Jam koreksi:
                                    {{ $item->correction_time_in ? \Carbon\Carbon::parse($item->correction_time_in)->format('H:i') : '-' }}
                                    -
                                    {{ $item->correction_time_out ? \Carbon\Carbon::parse($item->correction_time_out)->format('H:i') : '-' }}
                                </div>
                                <div class="small mb-1">{{ $item->reason }}</div>
                                <div class="small mb-1">
                                    @if ($item->document_path)
                                        <a href="{{ route('user.absence.view', $item->id) }}" target="_blank"
                                            rel="noopener">Lihat</a>
                                        |
                                        <a href="{{ route('user.absence.download', $item->id) }}">Unduh</a>
                                    @else
                                        <span class="text-muted">Dokumen: -</span>
                                    @endif
                                </div>
                                <div class="small mb-1">
                                    <span class="status-pill {{ $pillClass }}">{{ strtoupper($status) }}</span>
                                </div>
                                <div class="small text-muted">Catatan: {{ $item->admin_note ?: '-' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script-bottom')
    <script>
        (function() {
            var start = document.getElementById('absence_date');
            var end = document.getElementById('absence_end_date');
            var hint = document.getElementById('absence-days-hint');
            if (!start || !end) return;
            var MAX = {{ \App\Models\AbsenceRequest::MAX_RANGE_DAYS }};

            function parse(v) {
                var p = (v || '').split('-');
                return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : null;
            }

            function update() {
                end.min = start.value || '';
                if (start.value && (!end.value || end.value < start.value)) {
                    end.value = start.value;
                }
                var a = parse(start.value), b = parse(end.value);
                if (!a || !b) return;
                var days = Math.round((b - a) / 86400000) + 1;
                hint.textContent = days > MAX ?
                    'Rentang ' + days + ' hari melebihi batas maksimal ' + MAX + ' hari.' :
                    days + ' hari. Isi tanggal yang sama bila hanya 1 hari (maksimal ' + MAX + ' hari).';
                hint.className = days > MAX ? 'text-danger' : 'text-muted';
            }
            start.addEventListener('change', update);
            end.addEventListener('change', update);
            update();
        })();
    </script>
@endsection
