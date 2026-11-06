@extends('layouts.master')

@section('css')
    <style>
        .division-create-form {
            display: flex;
            gap: 8px;
            align-items: center;
            max-width: 640px;
        }

        .division-create-form .form-control {
            flex: 1 1 auto;
            min-width: 320px;
        }

        .division-edit-form {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100%;
        }

        .division-edit-form .form-control {
            flex: 1 1 auto;
            min-width: 420px;
        }

        .division-edit-form .form-control[readonly] {
            background: #f8fafc;
            cursor: not-allowed;
        }

        .division-edit-actions {
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }

        .division-edit-actions .is-hidden {
            display: none;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Divisi</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Divisi</a></li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('divisions.store') }}" class="division-create-form mb-3">
                @csrf
                <input type="text" class="form-control" name="name" placeholder="Nama divisi" required>
                <button type="submit" class="btn btn-primary btn-sm">Tambah Divisi</button>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Divisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($divisions as $division)
                            <tr>
                                <td>{{ $division->id }}</td>
                                <td>
                                    <form method="POST" action="{{ route('divisions.update', $division->id) }}"
                                        class="division-edit-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" class="form-control form-control-sm js-division-name"
                                            name="name" value="{{ $division->name }}"
                                            data-original="{{ $division->name }}" readonly required>
                                        <div class="division-edit-actions">
                                            <button type="button"
                                                class="btn btn-sm btn-warning js-division-edit-btn">Ubah</button>
                                            <button type="submit"
                                                class="btn btn-sm btn-info js-division-save-btn is-hidden">Simpan</button>
                                            <button type="button"
                                                class="btn btn-sm btn-secondary js-division-cancel-btn is-hidden">Batal</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('divisions.destroy', $division->id) }}"
                                        onsubmit="return confirm('Hapus divisi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data divisi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var forms = document.querySelectorAll('.division-edit-form');
            forms.forEach(function(form) {
                var input = form.querySelector('.js-division-name');
                var editBtn = form.querySelector('.js-division-edit-btn');
                var saveBtn = form.querySelector('.js-division-save-btn');
                var cancelBtn = form.querySelector('.js-division-cancel-btn');
                if (!input || !editBtn || !saveBtn || !cancelBtn) return;

                editBtn.addEventListener('click', function() {
                    input.readOnly = false;
                    input.focus();
                    editBtn.classList.add('is-hidden');
                    saveBtn.classList.remove('is-hidden');
                    cancelBtn.classList.remove('is-hidden');
                });

                cancelBtn.addEventListener('click', function() {
                    input.value = input.getAttribute('data-original') || '';
                    input.readOnly = true;
                    editBtn.classList.remove('is-hidden');
                    saveBtn.classList.add('is-hidden');
                    cancelBtn.classList.add('is-hidden');
                });
            });
        })();
    </script>
@endsection
