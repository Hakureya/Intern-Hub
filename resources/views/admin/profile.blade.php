@extends('layouts.master')

@section('css')
    <style>
        .pf-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .pf-sub {
            margin: 2px 0 20px;
            color: #6b7c8c;
            font-size: 13px;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Profil</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"> <a href="/admin/profile">Profil</a></li>
        </ol>
    </div>
@endsection

@section('content')
    @php
        $selectedDivisionId = old('division_id', $user->division_id);
        $selectedDivision = $divisions->firstWhere('id', (int) $selectedDivisionId);
    @endphp

    <div class="row">
        {{-- Kartu profil (kiri): ikut berubah sesuai isian form --}}
        <div class="col-lg-4 mb-3">
            @include('includes.profile-card', [
                'formId' => 'profileForm',
                'ids' => ['name' => 'p_name', 'email' => 'p_email', 'division' => 'p_division'],
                'name' => old('name', $user->name),
                'email' => old('email', $user->email),
                'roleLabel' => $roleLabel,
                'division' => optional($selectedDivision)->name,
                'photoUrl' => $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : null,
            ])
        </div>

        {{-- Form --}}
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="pf-title">Informasi pribadi</h5>
                    <p class="pf-sub">Perbarui foto, nama, email, dan divisi akunmu.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.profile.update') }}" id="profileForm"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label for="p_name">Nama lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="p_name" class="form-control"
                                value="{{ old('name', $user->name) }}" maxlength="255" required>
                        </div>

                        <div class="form-group">
                            <label for="p_email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="p_email" class="form-control"
                                value="{{ old('email', $user->email) }}" maxlength="255" required>
                        </div>

                        <div class="form-group">
                            <label for="p_division">Divisi</label>
                            <select name="division_id" id="p_division" class="form-control">
                                <option value="">- Belum dipilih -</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}"
                                        {{ (string) $selectedDivisionId === (string) $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
