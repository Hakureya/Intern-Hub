<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRequest;
use App\Models\Attendance;
use App\Models\AttendanceDraftDocument;
use App\Models\Division;
use App\Models\FinalReport;
use App\Models\Leave;
use App\Models\Mentor;
use App\Models\MonthlyReport;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Manajemen akun: tambah, ubah, nonaktifkan/aktifkan, reset password, hapus.
 * Hanya dapat diakses role super_admin (diatur di routes/web.php).
 */
class AccountManagementController extends Controller
{
    private const ROLE_LABELS = [
        'user' => 'Peserta Magang',
        'admin' => 'Admin',
        'mentor' => 'Mentor',
        'super_admin' => 'Super Admin',
    ];

    private const ROLE_NAMES = [
        'user' => 'Pengguna',
        'admin' => 'Administrator',
        'mentor' => 'Mentor',
        'super_admin' => 'Super Administrator',
    ];

    private const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/';

    public function index()
    {
        $users = User::with(['roles', 'division'])->orderBy('name')->get();

        $withRecords = $this->userIdsWithRecords($users->pluck('id')->all());

        $accounts = $users->map(function (User $user) use ($withRecords) {
            return $this->present($user, isset($withRecords[$user->id]));
        })->values();

        return view('admin.accounts', [
            'accounts' => $accounts,
            'accountMap' => $accounts->keyBy('id'),
            'divisions' => Division::orderBy('name')->get(),
            'mentors' => Mentor::orderBy('name')->get(),
            'stats' => [
                'total' => $accounts->count(),
                'active' => $accounts->where('is_active', true)->count(),
                'inactive' => $accounts->where('is_active', false)->count(),
            ],
            'currentUserId' => auth()->id(),
            'roleLabels' => self::ROLE_LABELS,
        ]);
    }

    public function store(Request $request)
    {
        $role = $request->input('role');

        $validated = $request->validate(
            $this->rules($role, null, true),
            $this->messages(),
            $this->attributes()
        );

        if ($error = $this->mentorDivisionError($validated)) {
            return back()->withErrors(['mentor_id' => $error])->withInput();
        }

        DB::transaction(function () use ($validated, $role, $request) {
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->is_active = true;
            // Divisi dapat diisi untuk semua role.
            $user->division_id = $validated['division_id'] ?? null;

            $attrs = [];
            if ($role === 'user') {
                $attrs = $this->pesertaAttributes($validated);
                $user->forceFill($attrs);
            }

            $user->save();

            $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => self::ROLE_NAMES[$role]]);
            $user->roles()->syncWithoutDetaching([$roleModel->id]);

            if ($role === 'user') {
                $this->ensureDefaultSchedule($user);
            }
        });

        flash()->success('Berhasil', 'Akun ' . self::ROLE_LABELS[$role] . ' berhasil ditambahkan.');

        return redirect()->route('accounts.index');
    }

    public function update(Request $request, $id)
    {
        $user = User::with('roles')->findOrFail($id);
        $currentRole = $this->primaryRole($user->roles->pluck('slug')->all());
        $isSelf = (int) $user->id === (int) auth()->id();
        $oldEmail = $user->email;

        // Role akun sendiri tidak bisa diubah; akun lain boleh.
        $role = $isSelf ? $currentRole : ($request->input('role') ?: $currentRole);

        $rules = $this->rules($role, $user->id, false);
        if (! $isSelf) {
            $rules['role'] = ['required', Rule::in(array_keys(self::ROLE_LABELS))];
        }

        $validated = $request->validate($rules, $this->messages(), $this->attributes());

        if ($error = $this->mentorDivisionError($validated)) {
            return back()->withErrors(['mentor_id' => $error])->withInput();
        }

        if (! $isSelf && $currentRole === 'super_admin' && $role !== 'super_admin'
            && $this->wouldRemoveLastActiveSuperAdmin($user)) {
            return back()
                ->withErrors(['role' => 'Minimal harus ada satu Super Admin yang aktif.'])
                ->withInput();
        }

        DB::transaction(function () use ($user, $validated, $role, $currentRole, $oldEmail, $request) {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->division_id = $validated['division_id'] ?? null;

            $attrs = [];
            if ($role === 'user') {
                $attrs = $this->pesertaAttributes($validated);
                $attrs += $this->handlePhoto($request, $user->profile_photo_path);
                $user->forceFill($attrs);
            }

            $user->save();

            if ($role !== $currentRole && $role !== null) {
                $managedIds = Role::whereIn('slug', array_keys(self::ROLE_LABELS))->pluck('id')->all();
                $newRole = Role::firstOrCreate(['slug' => $role], ['name' => self::ROLE_NAMES[$role]]);

                $user->roles()->detach($managedIds);
                $user->roles()->attach($newRole->id);
            }

            if ($role === 'user') {
                $this->ensureDefaultSchedule($user);
            }
        });

        flash()->success('Berhasil', 'Informasi akun berhasil diperbarui.');

        return redirect()->route('accounts.index');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->isActive()) {
            if ((int) $user->id === (int) auth()->id()) {
                flash()->error('Gagal', 'Kamu tidak bisa menonaktifkan akunmu sendiri.');
                return redirect()->route('accounts.index');
            }

            if ($this->wouldRemoveLastActiveSuperAdmin($user)) {
                flash()->error('Gagal', 'Minimal harus ada satu Super Admin yang aktif.');
                return redirect()->route('accounts.index');
            }

            $user->is_active = false;
            $user->save();

            flash()->success('Berhasil', 'Akun ' . $user->name . ' dinonaktifkan.');
        } else {
            $user->is_active = true;
            $user->save();

            flash()->success('Berhasil', 'Akun ' . $user->name . ' diaktifkan kembali.');
        }

        return redirect()->route('accounts.index');
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'regex:' . self::PASSWORD_REGEX, 'confirmed'],
        ], $this->messages(), $this->attributes());

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            // Matikan cookie "ingat saya" lama.
            'remember_token' => Str::random(60),
        ])->save();

        flash()->success('Berhasil', 'Password akun ' . $user->name . ' berhasil direset.');

        return redirect()->route('accounts.index');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::with('roles')->findOrFail($id);

        if ((int) $user->id === (int) auth()->id()) {
            flash()->error('Gagal', 'Kamu tidak bisa menghapus akunmu sendiri.');
            return redirect()->route('accounts.index');
        }

        if ($this->wouldRemoveLastActiveSuperAdmin($user)) {
            flash()->error('Gagal', 'Minimal harus ada satu Super Admin yang aktif.');
            return redirect()->route('accounts.index');
        }

        $name = $user->name;

        // Semua penghapusan akun wajib dikonfirmasi dengan mengetik nama akun.
        if (trim((string) $request->input('confirm_name')) !== $name) {
            flash()->error('Gagal', 'Nama yang diketik tidak sama dengan nama akun. Penghapusan dibatalkan.');
            return redirect()->route('accounts.index');
        }

        // Profil peserta tersimpan di akun. Akun yang sudah punya data absensi/laporan
        // hanya dihapus lewat permintaan force (dikirim modal Hapus), beserta seluruh datanya.
        if ($user->hasRecords() && ! $request->boolean('force')) {
            flash()->error('Gagal', 'Akun ' . $name . ' sudah punya data absensi atau laporan. Hapus lewat tombol Hapus, atau nonaktifkan saja akunnya.');
            return redirect()->route('accounts.index');
        }

        $photoPath = $user->profile_photo_path;
        $files = [];

        DB::transaction(function () use ($user, &$files) {
            $files = $user->purgeRecords();
            $user->roles()->detach();
            $user->delete();
        });

        // File dihapus setelah database berhasil diubah.
        foreach (array_filter(array_merge($files, [$photoPath])) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        flash()->success('Berhasil', 'Akun ' . $name . ' berhasil dihapus.');

        return redirect()->route('accounts.index');
    }

    /* ------------------------------------------------------------------ */
    /* Helper                                                              */
    /* ------------------------------------------------------------------ */

    private function rules($role, $ignoreUserId, bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            // Semua role dapat memiliki divisi.
            'division_id' => ['nullable', 'exists:divisions,id'],
        ];

        if ($creating) {
            $rules['role'] = ['required', Rule::in(array_keys(self::ROLE_LABELS))];
            $rules['password'] = ['required', 'string', 'min:8', 'regex:' . self::PASSWORD_REGEX, 'confirmed'];
        }

        // Peserta magang punya data lebih lengkap; admin & super admin cukup nama, email, password, divisi.
        if ($role === 'user') {
            $rules += [
                'phone_number' => ['required', 'string', 'max:25'],
                'major' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:500'],
                'birth_date' => ['nullable', 'date'],
                'mentor_id' => ['nullable', 'exists:mentors,id'],
                'internship_start_date' => ['nullable', 'date'],
                'internship_end_date' => ['nullable', 'date', 'after_or_equal:internship_start_date'],
            ];

            // Atribut tambahan hanya ada di form ubah akun.
            if (! $creating) {
                $rules += [
                    'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                    'emergency_contact_phone' => ['nullable', 'string', 'max:25'],
                    'emergency_contact_relation' => ['nullable', 'string', 'max:255'],
                    'bank_name' => ['nullable', 'string', 'max:255'],
                    'bank_account_number' => ['nullable', 'string', 'max:50'],
                    'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                ];
            }
        }

        return $rules;
    }

    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format :attribute tidak valid.',
            'unique' => ':attribute sudah digunakan akun lain.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'min' => ':attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'date' => ':attribute bukan tanggal yang valid.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'in' => ':attribute yang dipilih tidak valid.',
            'after_or_equal' => ':attribute tidak boleh lebih awal dari tanggal mulai.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat JPG, PNG, atau WEBP.',
            'digits_between' => ':attribute harus berupa angka 4 sampai 8 digit.',
            'profile_photo.max' => 'Ukuran foto maksimal 2 MB.',
            'password.regex' => 'Password minimal 8 karakter dan harus mengandung huruf besar, huruf kecil, angka, dan simbol.',
        ];
    }

    private function attributes(): array
    {
        return [
            'role' => 'Role',
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'phone_number' => 'Nomor telepon',
            'major' => 'Jurusan',
            'address' => 'Alamat',
            'birth_date' => 'Tanggal lahir',
            'division_id' => 'Divisi',
            'mentor_id' => 'Mentor',
            'internship_start_date' => 'Tanggal mulai magang',
            'internship_end_date' => 'Tanggal selesai magang',
            'emergency_contact_name' => 'Nama kontak darurat',
            'emergency_contact_phone' => 'Telepon kontak darurat',
            'emergency_contact_relation' => 'Hubungan kontak darurat',
            'bank_name' => 'Nama bank',
            'bank_account_number' => 'Nomor rekening',
            'profile_photo' => 'Foto profil',
        ];
    }

    private function mentorDivisionError(array $data): ?string
    {
        if (empty($data['mentor_id']) || empty($data['division_id'])) {
            return null;
        }

        $mentor = Mentor::find($data['mentor_id']);

        if ($mentor && (int) $mentor->division_id !== (int) $data['division_id']) {
            return 'Mentor tidak berada di divisi yang dipilih.';
        }

        return null;
    }

    private function primaryRole(array $slugs): ?string
    {
        foreach (['super_admin', 'admin', 'mentor', 'user'] as $slug) {
            if (in_array($slug, $slugs, true)) {
                return $slug;
            }
        }

        return null;
    }

    private function wouldRemoveLastActiveSuperAdmin(User $target): bool
    {
        $isSuperAdmin = $target->roles->contains('slug', 'super_admin');

        if (! $isSuperAdmin || ! $target->isActive()) {
            return false;
        }

        $otherActive = User::where('id', '!=', $target->id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))
            ->count();

        return $otherActive === 0;
    }

    /** Mentor mengikuti divisi: jika tidak dipilih manual, ambil mentor pertama (urut nama) di divisi itu. */
    private function resolveMentorId(array $data): ?int
    {
        if (! empty($data['mentor_id'])) {
            return (int) $data['mentor_id'];
        }

        if (! empty($data['division_id'])) {
            $id = Mentor::where('division_id', $data['division_id'])->orderBy('name')->value('id');

            return $id ? (int) $id : null;
        }

        return null;
    }

    /** Atribut profil peserta; seluruhnya disimpan di tabel users. */
    private function pesertaAttributes(array $data): array
    {
        $keys = [
            'phone_number', 'address', 'birth_date', 'division_id',
            'internship_start_date', 'internship_end_date',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'bank_name', 'bank_account_number', 'major',
        ];

        $attrs = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $attrs[$key] = $data[$key];
            }
        }

        $attrs['mentor_id'] = $this->resolveMentorId($data);

        return $attrs;
    }

    /** Unggah / hapus foto profil. Mengembalikan atribut yang perlu diubah (kosong jika tidak ada). */
    private function handlePhoto(Request $request, ?string $currentPath): array
    {
        $deleteCurrent = function () use ($currentPath) {
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }
        };

        if ($request->hasFile('profile_photo')) {
            $deleteCurrent();

            return ['profile_photo_path' => $request->file('profile_photo')->store('profile-photos', 'public')];
        }

        if ($request->boolean('remove_photo')) {
            $deleteCurrent();

            return ['profile_photo_path' => null];
        }

        return [];
    }

    /** Pastikan peserta punya jadwal bawaan. Profilnya ada di akun (users). */
    private function ensureDefaultSchedule(User $user): void
    {
        if ($user->schedules()->count() === 0) {
            $schedule = Schedule::where('time_in', Schedule::timeInShort())->where('time_out', Schedule::timeOutShort())->first()
                ?: Schedule::first();

            if (! $schedule) {
                $schedule = new Schedule();
                $schedule->slug = Schedule::defaultSlug();
                $schedule->time_in = Schedule::timeInShort();
                $schedule->time_out = Schedule::timeOutShort();
                $schedule->save();
            }

            $user->schedules()->sync([$schedule->id]);
        }
    }

    /** ID peserta (dari daftar) yang sudah punya data absensi/izin/laporan. */
    private function userIdsWithRecords(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $found = [];
        $models = [
            Attendance::class, Leave::class,
            AbsenceRequest::class, FinalReport::class, MonthlyReport::class, AttendanceDraftDocument::class,
        ];

        foreach ($models as $model) {
            foreach ($model::whereIn('user_id', $ids)->distinct()->pluck('user_id') as $id) {
                $found[$id] = true;
            }
        }

        return $found;
    }

    private function present(User $user, bool $hasRecords = false): array
    {
        $role = $this->primaryRole($user->roles->pluck('slug')->all());
        $date = fn ($value) => $value ? substr((string) $value, 0, 10) : '';
        $pick = fn ($key) => $user->{$key};

        $photoPath = $user->profile_photo_path;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'role_label' => self::ROLE_LABELS[$role] ?? 'Tanpa role',
            'is_active' => $user->isActive(),
            'is_self' => (int) $user->id === (int) auth()->id(),
            'has_records' => $hasRecords,
            'summary' => optional($user->division)->name ?: '',

            // Nilai untuk form ubah akun
            'phone_number' => $pick('phone_number'),
            'address' => $pick('address'),
            'birth_date' => $date($pick('birth_date')),
            'major' => $user->major,
            'division_id' => $pick('division_id'),
            'mentor_id' => $pick('mentor_id'),
            'internship_start_date' => $date($pick('internship_start_date')),
            'internship_end_date' => $date($pick('internship_end_date')),
            'emergency_contact_name' => $pick('emergency_contact_name'),
            'emergency_contact_phone' => $pick('emergency_contact_phone'),
            'emergency_contact_relation' => $pick('emergency_contact_relation'),
            'bank_name' => $pick('bank_name'),
            'bank_account_number' => $pick('bank_account_number'),
            'photo_url' => $photoPath ? asset('storage/' . $photoPath) : null,
        ];
    }
}
