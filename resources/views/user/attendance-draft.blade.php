@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=1" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @include('layouts.user-navbar', ['active' => 'history_draft'])

    <div class="draft-page">
        <div class="draft-shell">
            <div class="draft-card mb-3">
                <div class="draft-title">Upload Draft Dokumen Presensi</div>
                @if (!$user)
                    <div class="alert alert-warning mb-0">Data peserta magang belum tersedia. Lengkapi profil terlebih
                        dahulu.</div>
                @else
                    <form method="POST" action="{{ route('user.attendance.draft.store') }}" enctype="multipart/form-data"
                        class="form-row">
                        @csrf
                        <div class="form-group col-md-4">
                            <label>Bulan</label>
                            <input type="month" class="form-control" name="report_month"
                                value="{{ old('report_month', now()->format('Y-m')) }}" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>File Draft Presensi</label>
                            <input type="file" class="form-control" name="draft_document"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Format: pdf/doc/docx/xls/xlsx/jpg/png, maksimal 5MB.</small>
                        </div>
                        <div class="form-group col-12 mb-0">
                            <button class="btn btn-primary btn-sm" type="submit">Unggah Draft</button>
                        </div>
                    </form>
                @endif
            </div>

            <div class="draft-card">
                <div class="draft-title">Riwayat Draft Presensi</div>
                @if ($documents->isEmpty())
                    <div class="text-muted">Belum ada draft presensi yang diunggah.</div>
                @else
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Tanggal Unggah</th>
                                    <th>Dokumen</th>
                                    <th>Ukuran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($documents as $item)
                                    <tr>
                                        <td>{{ $item->report_month }}</td>
                                        <td>{{ optional($item->created_at)->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <a target="_blank" rel="noopener"
                                                href="{{ route('user.attendance.draft.view', $item->id) }}">Lihat</a>
                                            |
                                            <a
                                                href="{{ route('user.attendance.draft.download', $item->id) }}">{{ $item->file_name }}</a>
                                        </td>
                                        <td>
                                            @php
                                                $size = (int) ($item->file_size ?? 0);
                                                $displaySize = '-';
                                                if ($size > 0) {
                                                    $displaySize = number_format($size / 1024, 1) . ' KB';
                                                }
                                            @endphp
                                            {{ $displaySize }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-md-none">
                        @foreach ($documents as $item)
                            <div class="border rounded p-2 mb-2 bg-white">
                                <div class="font-weight-bold">{{ $item->report_month }}</div>
                                <div class="small text-muted">{{ optional($item->created_at)->format('d-m-Y H:i') }}</div>
                                <div class="small mt-1">
                                    <a target="_blank" rel="noopener"
                                        href="{{ route('user.attendance.draft.view', $item->id) }}">Lihat</a>
                                    |
                                    <a
                                        href="{{ route('user.attendance.draft.download', $item->id) }}">{{ $item->file_name }}</a>
                                </div>
                                <div class="small mt-1 text-muted">
                                    @php
                                        $size = (int) ($item->file_size ?? 0);
                                        $displaySize = '-';
                                        if ($size > 0) {
                                            $displaySize = number_format($size / 1024, 1) . ' KB';
                                        }
                                    @endphp
                                    Ukuran: {{ $displaySize }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
