@extends('layouts.master')

@section('css')
    <style>
        .mentor-create-form {
            display: flex;
            gap: 8px;
            align-items: center;
            max-width: 960px;
            flex-wrap: wrap;
        }

        .mentor-create-form .mentor-division {
            min-width: 260px;
        }

        .mentor-create-form .mentor-name {
            flex: 1 1 auto;
            min-width: 380px;
        }

        .mentor-edit-form {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
        }

        .mentor-edit-form .mentor-division {
            min-width: 220px;
        }

        .mentor-edit-form .mentor-name {
            flex: 1 1 auto;
            min-width: 320px;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Mentor</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Mentor</a></li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('mentors.store') }}" class="mentor-create-form mb-3">
                @csrf
                <select class="form-control mentor-division" name="division_id" required>
                    <option value="">Pilih Divisi</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>
                <input type="text" class="form-control mentor-name" name="name" placeholder="Nama mentor" required>
                <button type="submit" class="btn btn-primary btn-sm">Tambah Mentor</button>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Divisi</th>
                            <th>Nama Mentor</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mentors as $mentor)
                            <tr>
                                <td>{{ $mentor->id }}</td>
                                <td>
                                    <form method="POST" action="{{ route('mentors.update', $mentor->id) }}"
                                        class="mentor-edit-form">
                                        @csrf
                                        @method('PATCH')
                                        <select class="form-control form-control-sm mentor-division" name="division_id"
                                            required>
                                            @foreach ($divisions as $division)
                                                <option value="{{ $division->id }}"
                                                    {{ (int) $mentor->division_id === (int) $division->id ? 'selected' : '' }}>
                                                    {{ $division->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm mentor-name" name="name"
                                        value="{{ $mentor->name }}" required>
                                    <button type="submit" class="btn btn-sm btn-info">Simpan</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('mentors.destroy', $mentor->id) }}"
                                        onsubmit="return confirm('Hapus mentor ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data mentor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
