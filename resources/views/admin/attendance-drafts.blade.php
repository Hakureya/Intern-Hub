@extends('layouts.master')

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Draft Presensi</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Draft Presensi</a></li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('attendance-drafts.index') }}" class="form-inline mb-3">
                <input type="month" class="form-control mr-2 mb-2" name="month" value="{{ $selectedMonth }}">
                <select class="form-control mr-2 mb-2" name="user_id">
                    <option value="">Peserta Magang (Semua)</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}"
                            {{ (string) ($selectedUserId ?? '') === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm mb-2" type="submit">Tampilkan</button>
            </form>

            @if ($documents->isEmpty())
                <div class="text-muted">Belum ada draft presensi pada filter ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Tanggal Upload</th>
                                <th>Peserta</th>
                                <th>Pengunggah</th>
                                <th>Dokumen</th>
                                <th>Ukuran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $item)
                                <tr>
                                    <td>{{ $item->report_month }}</td>
                                    <td>{{ optional($item->created_at)->format('d-m-Y H:i') }}</td>
                                    <td>{{ optional($item->user)->name ?? '-' }}</td>
                                    <td>{{ optional($item->uploadedBy)->name ?? '-' }}</td>
                                    <td>
                                        <a target="_blank" rel="noopener"
                                            href="{{ route('attendance-drafts.view', $item->id) }}">Lihat</a>
                                        |
                                        <a
                                            href="{{ route('attendance-drafts.download', $item->id) }}">{{ $item->file_name }}</a>
                                    </td>
                                    <td>
                                        @php
                                            $size = (int) ($item->file_size ?? 0);
                                            $displaySize = $size > 0 ? number_format($size / 1024, 1) . ' KB' : '-';
                                        @endphp
                                        {{ $displaySize }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
