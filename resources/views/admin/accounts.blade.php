@extends('layouts.master')

@section('css')
    <style>
        .acc-stat {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .acc-stat-item {
            flex: 1 1 160px;
            padding: 14px 18px;
            border: 1px solid #e3ebf1;
            border-radius: 10px;
            background: #fff;
        }

        .acc-stat-item strong {
            display: block;
            font-size: 24px;
            line-height: 1.2;
        }

        .acc-stat-item span {
            font-size: 12px;
            color: #6b7c8c;
        }

        .acc-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 14px;
        }

        .acc-toolbar .form-control {
            width: auto;
            min-width: 170px;
        }

        .acc-toolbar .acc-add {
            margin-left: auto;
        }

        .acc-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .acc-actions form {
            margin: 0;
        }

        .acc-muted {
            color: #8a98a5;
            font-size: 12px;
        }

        .acc-section-title {
            margin: 6px 0 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7c8c;
        }

        .acc-photo {
            display: block;
            width: 72px;
            height: 72px;
            margin-bottom: 8px;
            border-radius: 10px;
            border: 1px solid #d9e7ef;
            object-fit: cover;
        }

        .acc-password-row {
            display: flex;
            gap: 8px;
        }

        .acc-password-row .form-control {
            flex: 1 1 auto;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Manajemen Akun</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Manajemen Akun</a></li>
        </ol>
    </div>
@endsection

@section('content')
    <div class="acc-stat">
        <div class="acc-stat-item"><strong>{{ $stats['total'] }}</strong><span>Total akun</span></div>
        <div class="acc-stat-item"><strong>{{ $stats['active'] }}</strong><span>Akun aktif</span></div>
        <div class="acc-stat-item"><strong>{{ $stats['inactive'] }}</strong><span>Akun nonaktif</span></div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="acc-toolbar">
                <select id="filter-role" class="form-control form-control-sm" aria-label="Filter role">
                    <option value="">Semua role</option>
                    @foreach ($roleLabels as $slug => $label)
                        <option value="role-{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select id="filter-status" class="form-control form-control-sm" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="status-active">Aktif</option>
                    <option value="status-inactive">Nonaktif</option>
                </select>
                <button type="button" class="btn btn-primary btn-sm acc-add" id="btn-create-account">
                    <i class="ti-plus"></i> Tambah Akun
                </button>
            </div>

            <div class="table-responsive">
                <table id="accounts-table" class="table table-striped table-bordered mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Divisi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            @php $isSelf = (int) $account['id'] === (int) $currentUserId; @endphp
                            <tr>
                                <td>{{ $account['name'] }}@if ($isSelf) <span class="acc-muted">(kamu)</span>@endif</td>
                                <td>{{ $account['email'] }}</td>
                                <td data-search="role-{{ $account['role'] }} {{ $account['role_label'] }}"><span class="badge badge-{{ $account['role'] === 'super_admin' ? 'danger' : ($account['role'] === 'admin' ? 'info' : 'secondary') }}">{{ $account['role_label'] }}</span></td>
                                <td>{{ $account['summary'] ?: '-' }}</td>
                                <td data-search="status-{{ $account['is_active'] ? 'active' : 'inactive' }} {{ $account['is_active'] ? 'Aktif' : 'Nonaktif' }}"><span class="badge badge-{{ $account['is_active'] ? 'success' : 'warning' }}">{{ $account['is_active'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td>
                                    <div class="acc-actions">
                                        <button type="button" class="btn btn-sm btn-warning js-edit"
                                            data-id="{{ $account['id'] }}">Ubah</button>
                                        <button type="button" class="btn btn-sm btn-info js-reset"
                                            data-id="{{ $account['id'] }}">Reset Password</button>

                                        @unless ($isSelf)
                                        <form method="POST" action="{{ route('accounts.status', $account['id']) }}"
                                            data-confirm="{{ $account['is_active'] ? 'Nonaktifkan akun ' . $account['name'] . '? Akun ini tidak bisa login sampai diaktifkan kembali.' : 'Aktifkan kembali akun ' . $account['name'] . '?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-sm {{ $account['is_active'] ? 'btn-secondary' : 'btn-success' }}">
                                                {{ $account['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-danger js-purge"
                                            data-id="{{ $account['id'] }}">Hapus</button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal tambah / ubah akun --}}
    <div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" id="accountForm" action="{{ route('accounts.store') }}" class="modal-content"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PATCH" id="accountMethod" disabled>
                <input type="hidden" name="_form" value="account">
                <input type="hidden" name="_account_id" id="accountId" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalTitle">Tambah Akun</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span
                            aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'account')
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="f_role">Role</label>
                        <select name="role" id="f_role" class="form-control" required>
                            @foreach ($roleLabels as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted" id="roleHelp"></small>
                        <small class="form-text text-warning" id="roleChangeNote" style="display:none">Mengubah role
                            akan mengubah hak akses akun ini.</small>
                        <small class="form-text text-muted" id="roleSelfNote" style="display:none">Role akunmu sendiri
                            tidak dapat diubah.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="f_name">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name" class="form-control" maxlength="255" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="f_email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="f_email" class="form-control" maxlength="255" required>
                        </div>
                    </div>

                    <div class="form-row js-password-fields">
                        <div class="form-group col-md-6">
                            <label for="f_password">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="f_password" class="form-control"
                                autocomplete="new-password" required>
                            <small class="form-text text-muted">Minimal 8 karakter, huruf besar, huruf kecil, angka, dan
                                simbol.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="f_password_confirmation">Konfirmasi Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="f_password_confirmation"
                                class="form-control" autocomplete="new-password" required>
                        </div>
                    </div>

                    {{-- Divisi: semua role. Mentor: hanya peserta, otomatis mengikuti divisi. --}}
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="f_division_id">Divisi</label>
                            <select name="division_id" id="f_division_id" class="form-control">
                                <option value="">- Belum dipilih -</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6 js-user-fields">
                            <label for="f_mentor_id">Mentor</label>
                            <select name="mentor_id" id="f_mentor_id" class="form-control">
                                <option value="">- Belum dipilih -</option>
                                @foreach ($mentors as $mentor)
                                    <option value="{{ $mentor->id }}" data-division="{{ $mentor->division_id }}">
                                        {{ $mentor->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Terpilih otomatis saat divisi dipilih; bisa diganti ke
                                mentor lain di divisi yang sama.</small>
                        </div>
                    </div>

                    {{-- Khusus role Peserta Magang --}}
                    <div class="js-user-fields">
                        <hr>
                        <p class="acc-section-title">Data peserta magang</p>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="f_phone_number">Nomor Telepon <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="f_phone_number" class="form-control"
                                    maxlength="25" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="f_birth_date">Tanggal Lahir</label>
                                <input type="date" name="birth_date" id="f_birth_date" class="form-control">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="f_major">Jurusan <span class="text-danger">*</span></label>
                                <input type="text" name="major" id="f_major" class="form-control" maxlength="255"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="f_address">Alamat</label>
                            <textarea name="address" id="f_address" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="f_internship_start_date">Mulai Magang</label>
                                <input type="date" name="internship_start_date" id="f_internship_start_date"
                                    class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="f_internship_end_date">Selesai Magang</label>
                                <input type="date" name="internship_end_date" id="f_internship_end_date"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    {{-- Atribut tambahan: hanya saat mengubah akun peserta --}}
                    <div class="js-user-extra">
                        <hr>
                        <p class="acc-section-title">Kontak darurat</p>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="f_emergency_contact_name">Nama</label>
                                <input type="text" name="emergency_contact_name" id="f_emergency_contact_name"
                                    class="form-control" maxlength="255">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="f_emergency_contact_phone">Telepon</label>
                                <input type="text" name="emergency_contact_phone" id="f_emergency_contact_phone"
                                    class="form-control" maxlength="25">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="f_emergency_contact_relation">Hubungan</label>
                                <input type="text" name="emergency_contact_relation" id="f_emergency_contact_relation"
                                    class="form-control" maxlength="255">
                            </div>
                        </div>

                        <p class="acc-section-title">Rekening bank</p>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="f_bank_name">Nama Bank</label>
                                <input type="text" name="bank_name" id="f_bank_name" class="form-control"
                                    maxlength="255">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="f_bank_account_number">Nomor Rekening</label>
                                <input type="text" name="bank_account_number" id="f_bank_account_number"
                                    class="form-control" maxlength="50">
                            </div>
                        </div>

                        <p class="acc-section-title">Foto profil</p>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <img id="currentPhoto" src="" alt="Foto profil saat ini" class="acc-photo"
                                    style="display:none">
                                <label for="f_profile_photo">Foto Profil</label>
                                <input type="file" name="profile_photo" id="f_profile_photo" class="form-control-file"
                                    accept=".jpg,.jpeg,.png,.webp">
                                <small class="form-text text-muted">JPG, PNG, atau WEBP, maksimal 2 MB.</small>
                                <div class="custom-control custom-checkbox mt-2" id="removePhotoWrap"
                                    style="display:none">
                                    <input type="checkbox" class="custom-control-input" name="remove_photo"
                                        id="f_remove_photo" value="1">
                                    <label class="custom-control-label" for="f_remove_photo">Hapus foto saat ini</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="accountSubmit">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal reset password --}}
    <div class="modal fade" id="resetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" id="resetForm" action="" class="modal-content">
                @csrf
                <input type="hidden" name="_form" value="reset">
                <input type="hidden" name="_account_id" id="resetAccountId" value="">

                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span
                            aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'reset')
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="mb-3">Atur password baru untuk <strong id="resetName">-</strong>.</p>

                    <div class="form-group">
                        <label for="r_password">Password Baru</label>
                        <div class="acc-password-row">
                            <input type="password" name="password" id="r_password" class="form-control"
                                autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary" id="btn-generate">Buat acak</button>
                        </div>
                        <small class="form-text text-muted">Minimal 8 karakter, huruf besar, huruf kecil, angka, dan
                            simbol.</small>
                    </div>
                    <div class="form-group">
                        <label for="r_password_confirmation">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" id="r_password_confirmation"
                            class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="r_show">
                        <label class="custom-control-label" for="r_show">Tampilkan password</label>
                    </div>
                    <p class="acc-muted mt-3 mb-0">Catat password ini dan sampaikan ke pemilik akun. Password tidak bisa
                        dilihat lagi setelah disimpan.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal hapus akun: dipakai untuk semua akun, wajib mengetik nama akun --}}
    <div class="modal fade" id="purgeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" id="purgeForm" action="" class="modal-content">
                @csrf
                @method('DELETE')
                <input type="hidden" name="force" value="1">

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Hapus Permanen</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span
                            aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <div id="purgeWithRecords" hidden>
                        <p>Akun <strong class="purge-name">-</strong> beserta <strong>seluruh datanya</strong> akan
                            dihapus permanen:</p>
                        <ul class="pl-3">
                            <li>absensi, keterlambatan, dan lembur</li>
                            <li>pengajuan izin dan dokumennya</li>
                            <li>laporan bulanan, laporan akhir, dan draf absensi beserta filenya</li>
                            <li>profil dan foto</li>
                        </ul>
                    </div>
                    <div id="purgeNoRecords" hidden>
                        <p>Akun <strong class="purge-name">-</strong> akan dihapus permanen, termasuk profil dan
                            fotonya.</p>
                    </div>
                    <p class="text-danger mb-3">Tindakan ini tidak bisa dibatalkan. Kalau hanya ingin menutup akses
                        login, gunakan <strong>Nonaktifkan</strong>.</p>

                    <div class="form-group mb-0">
                        <label for="purgeConfirm">Ketik nama akun untuk melanjutkan</label>
                        <input type="text" name="confirm_name" id="purgeConfirm" class="form-control"
                            autocomplete="off">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="purgeSubmit" disabled>Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script-bottom')
    <script>
        (function($) {
            var ACCOUNTS = @json($accountMap);
            var OLD = @json(old());
            var BASE = @json(url('/account-management'));
            var REOPEN = @json(old('_form'));
            var REOPEN_ID = @json(old('_account_id'));
            var FIELDS = ['role', 'name', 'email', 'phone_number', 'address', 'birth_date', 'major',
                'division_id', 'mentor_id', 'internship_start_date', 'internship_end_date',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
                'bank_name', 'bank_account_number'
            ];
            var mode = 'create';
            var originalRole = null;
            var ROLE_HELP = {
                'user': 'Peserta magang memiliki data lengkap: kontak, jurusan, divisi, mentor, dan periode magang.',
                'admin': 'Admin hanya memerlukan nama, email, password, dan divisi.',
                'super_admin': 'Super Admin hanya memerlukan nama, email, password, dan divisi.'
            };

            /* ---------- Tabel ---------- */
            var table = $('#accounts-table').DataTable({
                pageLength: 10,
                order: [
                    [0, 'asc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ akun',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ akun',
                    infoEmpty: 'Tidak ada akun',
                    infoFiltered: '(disaring dari _MAX_ akun)',
                    zeroRecords: 'Tidak ada akun yang cocok.',
                    paginate: {
                        previous: 'Sebelumnya',
                        next: 'Berikutnya'
                    }
                }
            });

            $('#filter-role').on('change', function() {
                table.column(2).search(this.value).draw();
            });
            $('#filter-status').on('change', function() {
                table.column(4).search(this.value).draw();
            });

            /* ---------- Modal tambah / ubah ---------- */
            var $form = $('#accountForm');

            function field(name) {
                return $form.find('[name="' + name + '"]');
            }

            function filterMentors() {
                var division = field('division_id').val();
                var $mentor = field('mentor_id');

                $mentor.find('option').each(function() {
                    var ok = !this.value || !division || String($(this).data('division')) === String(division);
                    $(this).prop('hidden', !ok).prop('disabled', !ok);
                });

                if ($mentor.find('option:selected').prop('disabled')) {
                    $mentor.val('');
                }
            }

            // Mentor otomatis terpilih mengikuti divisi (mentor pertama di divisi itu).
            function autoSelectMentor() {
                var division = field('division_id').val();
                var first = '';

                if (division) {
                    field('mentor_id').find('option').each(function() {
                        if (this.value && String($(this).data('division')) === String(division)) {
                            first = this.value;
                            return false;
                        }
                    });
                }

                field('mentor_id').val(first);
                filterMentors();
            }

            function toggleRoleFields() {
                var role = field('role').val();
                var isUser = role === 'user';
                var isEdit = mode === 'edit';

                $('.js-user-fields').toggle(isUser).find(':input').prop('disabled', !isUser);
                $('.js-user-extra').toggle(isUser && isEdit).find(':input').prop('disabled', !(isUser && isEdit));

                $('#roleHelp').text(ROLE_HELP[role] || '');
                $('#roleChangeNote').toggle(isEdit && originalRole !== null && role !== originalRole &&
                    !field('role').prop('disabled'));
                filterMentors();
            }

            function fill(values) {
                FIELDS.forEach(function(name) {
                    var value = values && values[name] != null ? values[name] : '';
                    field(name).val(value);
                });
            }

            function openCreate(values) {
                mode = 'create';
                originalRole = null;

                $('#accountModalTitle').text('Tambah Akun');
                $('#accountSubmit').text('Simpan Akun');
                $form.attr('action', BASE);
                $('#accountMethod').prop('disabled', true);
                $('#accountId').val('');

                $('.js-password-fields').show().find(':input').prop('disabled', false).val('');
                field('role').prop('disabled', false);
                $('#roleSelfNote').hide();

                fill(values || {
                    role: 'user'
                });
                if (!field('role').val()) field('role').val('user');
                toggleRoleFields();
                $('#accountModal').modal('show');
            }

            function openEdit(id, overrides) {
                var account = ACCOUNTS[id];
                if (!account) return;

                mode = 'edit';
                originalRole = account.role || 'user';

                $('#accountModalTitle').text('Ubah Akun');
                $('#accountSubmit').text('Simpan Perubahan');
                $form.attr('action', BASE + '/' + id);
                $('#accountMethod').prop('disabled', false);
                $('#accountId').val(id);

                $('.js-password-fields').hide().find(':input').prop('disabled', true);

                fill($.extend({}, account, overrides || {}));

                // Role akun lain boleh diubah; role akun sendiri dikunci.
                field('role').val((overrides && overrides.role) || originalRole).prop('disabled', !!account.is_self);
                $('#roleSelfNote').toggle(!!account.is_self);

                // Foto
                field('profile_photo').val('');
                $('#f_remove_photo').prop('checked', false);
                if (account.photo_url) {
                    $('#currentPhoto').attr('src', account.photo_url).show();
                    $('#removePhotoWrap').show();
                } else {
                    $('#currentPhoto').hide().attr('src', '');
                    $('#removePhotoWrap').hide();
                }

                toggleRoleFields();
                $('#accountModal').modal('show');
            }

            $('#btn-create-account').on('click', function() {
                openCreate();
            });
            $(document).on('click', '.js-edit', function() {
                openEdit($(this).data('id'));
            });
            field('role').on('change', toggleRoleFields);
            field('division_id').on('change', autoSelectMentor);

            /* ---------- Modal reset password ---------- */
            function openReset(id) {
                var account = ACCOUNTS[id];
                if (!account) return;

                $('#resetForm').attr('action', BASE + '/' + id + '/reset-password');
                $('#resetAccountId').val(id);
                $('#resetName').text(account.name + ' (' + account.email + ')');
                $('#r_password, #r_password_confirmation').val('').attr('type', 'password');
                $('#r_show').prop('checked', false);
                $('#resetModal').modal('show');
            }

            $(document).on('click', '.js-reset', function() {
                openReset($(this).data('id'));
            });

            /* ---------- Modal hapus permanen ---------- */
            var purgeName = '';

            $(document).on('click', '.js-purge', function() {
                var id = $(this).data('id');
                var account = ACCOUNTS[id];
                if (!account) return;

                purgeName = account.name;
                $('#purgeForm').attr('action', BASE + '/' + id);
                $('.purge-name').text(account.name);
                $('#purgeWithRecords').prop('hidden', !account.has_records);
                $('#purgeNoRecords').prop('hidden', !!account.has_records);
                $('#purgeConfirm').val('');
                $('#purgeSubmit').prop('disabled', true);
                $('#purgeModal').modal('show');
            });

            $('#purgeConfirm').on('input', function() {
                $('#purgeSubmit').prop('disabled', $(this).val().trim() !== purgeName);
            });

            $('#r_show').on('change', function() {
                $('#r_password, #r_password_confirmation').attr('type', this.checked ? 'text' : 'password');
            });

            $('#btn-generate').on('click', function() {
                var sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '!@#$%&*?'];
                var all = sets.join('');
                var buf = new Uint32Array(14);
                window.crypto.getRandomValues(buf);

                var chars = sets.map(function(set, i) {
                    return set.charAt(buf[i] % set.length);
                });
                for (var i = sets.length; i < buf.length; i++) {
                    chars.push(all.charAt(buf[i] % all.length));
                }
                // acak urutan
                for (var j = chars.length - 1; j > 0; j--) {
                    var k = buf[j] % (j + 1);
                    var tmp = chars[j];
                    chars[j] = chars[k];
                    chars[k] = tmp;
                }

                var password = chars.join('');
                $('#r_password, #r_password_confirmation').val(password).attr('type', 'text');
                $('#r_show').prop('checked', true);
            });

            /* ---------- Buka kembali modal jika validasi gagal ---------- */
            if (REOPEN === 'account') {
                if (REOPEN_ID) {
                    openEdit(REOPEN_ID, OLD);
                } else {
                    openCreate(OLD);
                }
            } else if (REOPEN === 'reset' && REOPEN_ID) {
                openReset(REOPEN_ID);
            }
        })(jQuery);
    </script>
@endsection
