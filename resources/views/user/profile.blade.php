@extends('layouts.master-blank')

@section('content')
@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=1" rel="stylesheet" type="text/css" />
@endsection
@php
    $displayUser = auth()->user();
    $cardPhotoPath = $displayUser->profile_photo_path ?? optional($user)->profile_photo_path;
    $cardDivisionId = old('division_id', $displayUser->division_id ?? optional($user)->division_id);
    $cardDivision = collect($divisions ?? [])->firstWhere('id', (int) $cardDivisionId);
@endphp
@include('layouts.user-navbar', ['active' => 'profile'])
<div class="attendance-page">
    <div class="attendance-shell">
        <div class="attendance-topbar">
            <div class="attendance-greeting">
                <span>Selamat datang kembali</span>
                <h1>{{ $displayUser->name }}</h1>
            </div>
        </div>

        <div class="row">
        <div class="col-lg-4 mb-3">
            @include('includes.profile-card', [
                'formId' => 'profileForm',
                'ids' => ['name' => 'name', 'email' => 'email', 'division' => 'division_id'],
                'name' => old('name', $displayUser->name),
                'email' => old('email', $displayUser->email),
                'roleLabel' => 'Peserta Magang',
                'stickyTop' => '88px',
                'division' => optional($cardDivision)->name,
                'photoUrl' => $cardPhotoPath ? asset('storage/' . $cardPhotoPath) : null,
            ])
        </div>
        <div class="col-lg-8">
        <div class="action-card" style="margin-top: 0;">
            <div class="action-header">
                <div class="action-title">Kelola Profil</div>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('user.profile.update') }}" id="profileForm" class="action-form"
                enctype="multipart/form-data">
                @csrf
                <div class="form-row">
                    <div class="col-12">
                        <div class="profile-section-title">Data Pribadi</div>
                        <div class="profile-section-divider"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">{{ __('global.name') }}</label>
                        <input id="name" type="text" class="form-control" name="name"
                            value="{{ old('name', $displayUser->name) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="phone_number">{{ __('global.phone_number') }}</label>
                        <input id="phone_number" type="text" class="form-control" name="phone_number"
                            value="{{ old('phone_number', $displayUser->phone_number) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="address">{{ __('global.address') }}</label>
                        <input id="address" type="text" class="form-control" name="address"
                            value="{{ old('address', $displayUser->address) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="birth_date">{{ __('global.birth_date') }}</label>
                        <input id="birth_date" type="date" class="form-control" name="birth_date"
                            value="{{ old('birth_date', $displayUser->birth_date) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="institution">{{ __('global.institution') }}</label>
                        <input id="institution" type="text" class="form-control" name="institution"
                            value="{{ old('institution', $displayUser->institution) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="internship_start_date">Periode Magang Mulai</label>
                        <input id="internship_start_date" type="date" class="form-control"
                            name="internship_start_date"
                            value="{{ old('internship_start_date', $displayUser->internship_start_date ?? optional($user)->internship_start_date) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="internship_end_date">Periode Magang Selesai</label>
                        <input id="internship_end_date" type="date" class="form-control" name="internship_end_date"
                            value="{{ old('internship_end_date', $displayUser->internship_end_date ?? optional($user)->internship_end_date) }}">
                    </div>

                    <div class="col-12">
                        <div class="profile-section-title">Data Profil</div>
                        <div class="profile-section-divider"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="division_id">Divisi</label>
                        @php
                            $selectedDivisionId = old(
                                'division_id',
                                $displayUser->division_id ?? optional($user)->division_id,
                            );
                            $mentorName = old('mentor_name');
                            if (!$mentorName) {
                                $mentorName =
                                    optional($displayUser->mentor)->name ??
                                    (optional(optional($user)->mentor)->name ?? '-');
                            }
                        @endphp
                        <select id="division_id" class="form-control" name="division_id" required>
                            <option value="">Pilih Divisi</option>
                            @foreach ($divisions ?? collect() as $division)
                                <option value="{{ $division->id }}"
                                    {{ (string) $selectedDivisionId === (string) $division->id ? 'selected' : '' }}>
                                    {{ $division->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="mentor_name">Mentor</label>
                        <input id="mentor_name" type="text" class="form-control" name="mentor_name"
                            value="{{ $mentorName }}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="major">{{ __('global.major') }}</label>
                        <input id="major" type="text" class="form-control" name="major"
                            value="{{ old('major', optional($user)->major) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">{{ __('global.email') }}</label>
                        <input id="email" type="email" class="form-control" name="email"
                            value="{{ old('email', $displayUser->email) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="profile-section-title">Kontak Darurat</div>
                        <div class="profile-section-divider"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="emergency_contact_name">Nama Kontak Darurat</label>
                        <input id="emergency_contact_name" type="text" class="form-control"
                            name="emergency_contact_name"
                            value="{{ old('emergency_contact_name', $displayUser->emergency_contact_name ?? optional($user)->emergency_contact_name) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="emergency_contact_phone">Nomor Kontak Darurat</label>
                        <input id="emergency_contact_phone" type="text" class="form-control"
                            name="emergency_contact_phone"
                            value="{{ old('emergency_contact_phone', $displayUser->emergency_contact_phone ?? optional($user)->emergency_contact_phone) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="emergency_contact_relation">Hubungan Kontak Darurat</label>
                        <input id="emergency_contact_relation" type="text" class="form-control"
                            name="emergency_contact_relation"
                            value="{{ old('emergency_contact_relation', $displayUser->emergency_contact_relation ?? optional($user)->emergency_contact_relation) }}">
                    </div>

                    <div class="col-12">
                        <div class="profile-section-title">Data Bank</div>
                        <div class="profile-section-divider"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="bank_name">Nama Bank</label>
                        <input id="bank_name" type="text" class="form-control" name="bank_name"
                            value="{{ old('bank_name', $displayUser->bank_name ?? optional($user)->bank_name) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="bank_account_number">Nomor Rekening</label>
                        <input id="bank_account_number" type="text" class="form-control"
                            name="bank_account_number"
                            value="{{ old('bank_account_number', $displayUser->bank_account_number ?? optional($user)->bank_account_number) }}">
                    </div>

                    <div class="col-12">
                        <div class="profile-section-title">Histori Magang</div>
                        <div class="profile-section-divider"></div>
                        @if (!empty($history) && $history->isNotEmpty())
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tipe</th>
                                            <th>Periode</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($history as $item)
                                            <tr>
                                                <td>{{ $item['type'] }}</td>
                                                <td>{{ $item['period'] }}</td>
                                                <td>{{ $item['date'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted mb-3">Belum ada histori magang.</div>
                        @endif
                    </div>
                </div>
                <button class="btn btn-primary action-button" type="submit">Simpan Profil</button>
            </form>
        </div>
        </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function() {
        var divisionSelect = document.getElementById('division_id');
        var mentorInput = document.getElementById('mentor_name');
        if (!divisionSelect || !mentorInput) return;

        var divisionMentorMap = @json(collect($divisions ?? [])->mapWithKeys(function ($division) {
                return [(string) $division->id => optional($division->mentors->first())->name ?: '-'];
            }));

        function syncMentor() {
            var selectedDivisionId = divisionSelect.value || '';
            mentorInput.value = divisionMentorMap[selectedDivisionId] || '-';
        }

        divisionSelect.addEventListener('change', syncMentor);
        syncMentor();
    })();
</script>
@endsection
