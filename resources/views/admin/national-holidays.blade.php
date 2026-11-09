@extends('layouts.master')

@section('css')
    <style>
        .holiday-filter-form {
            display: flex;
            gap: 8px;
            align-items: center;
            max-width: 520px;
            flex-wrap: wrap;
        }

        .holiday-filter-form .form-control {
            min-width: 220px;
        }

        .holiday-create-form {
            display: flex;
            gap: 8px;
            align-items: center;
            max-width: 1100px;
            flex-wrap: wrap;
        }

        .holiday-create-form .holiday-date {
            min-width: 200px;
        }

        .holiday-create-form .holiday-name {
            flex: 1 1 auto;
            min-width: 360px;
        }

        .holiday-create-form .holiday-type {
            min-width: 220px;
        }

        .holiday-edit-form {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
        }

        .holiday-edit-form .holiday-date {
            min-width: 160px;
        }

        .holiday-edit-form .holiday-name {
            flex: 1 1 auto;
            min-width: 300px;
        }

        .holiday-edit-form .holiday-type {
            min-width: 100px;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Cuti Nasional</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Cuti Nasional</a></li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="holiday-filter-form mb-3">
                <input type="month" class="form-control" name="month" value="{{ $selectedMonth }}">
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            <form method="POST" action="{{ route('national-holidays.store') }}" class="holiday-create-form mb-3">
                @csrf
                <input type="date" class="form-control holiday-date" name="holiday_date" required>
                <input type="text" class="form-control holiday-name" name="name" placeholder="Nama hari libur"
                    required>
                <select class="form-control holiday-type" name="type" required>
                    <option value="LH">LH - Libur Hari Besar</option>
                    <option value="CB">CB - Cuti Bersama</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Tambah</button>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holidays as $holiday)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($holiday->holiday_date)->format('d-m-Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('national-holidays.update', $holiday->id) }}"
                                        class="holiday-edit-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="date" class="form-control form-control-sm holiday-date"
                                            name="holiday_date" value="{{ $holiday->holiday_date }}" required>
                                        <input type="text" class="form-control form-control-sm holiday-name"
                                            name="name" value="{{ $holiday->name }}" required>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm holiday-type" name="type" required>
                                        <option value="LH" {{ $holiday->type === 'LH' ? 'selected' : '' }}>LH</option>
                                        <option value="CB" {{ $holiday->type === 'CB' ? 'selected' : '' }}>CB</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-info">Simpan</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('national-holidays.destroy', $holiday->id) }}"
                                        data-confirm="Hapus data ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data cuti/libur pada bulan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
