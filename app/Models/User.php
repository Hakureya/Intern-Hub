<?php

namespace App\Models; 
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_discoverable',
        'card_visibility',
        'phone_number',
        'address',
        'birth_date',
        'institution',
        'major',
        'division_id',
        'mentor_id',
        'internship_start_date',
        'internship_end_date',
        'profile_photo_path',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'bank_name',
        'bank_account_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_discoverable' => 'boolean',
        'card_visibility' => 'array',
    ];

    /** Isian yang boleh diatur tampil/tidaknya di kartu peserta (nama selalu tampil). */
    public const CARD_FIELDS = [
        'photo' => 'Foto',
        'division' => 'Divisi',
        'mentor' => 'Mentor',
        'major' => 'Jurusan',
        'institution' => 'Asal Kampus',
        'period' => 'Periode Magang',
        'email' => 'Email',
        'phone_number' => 'Nomor Telepon',
        'address' => 'Alamat',
        'birth_date' => 'Tanggal Lahir',
    ];

    /** Bawaan sebelum peserta mengatur sendiri: hanya foto + divisi yang tampil. */
    public const CARD_DEFAULTS = ['photo' => true, 'division' => true];

    /** Status tampil tiap isian kartu (lengkap, true/false). */
    public function cardVisibility(): array
    {
        $saved = is_array($this->card_visibility) ? $this->card_visibility : [];
        $result = [];

        foreach (array_keys(self::CARD_FIELDS) as $key) {
            $result[$key] = array_key_exists($key, $saved)
                ? (bool) $saved[$key]
                : (self::CARD_DEFAULTS[$key] ?? false);
        }

        return $result;
    }

    /** Peserta yang aktif dan mengizinkan dirinya ditemukan. */
    public function scopeDiscoverable($query)
    {
        return $query->peserta()
            ->where('users.is_active', 1)
            ->where('users.is_discoverable', 1);
    }

    private static function idDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $date->format('d') . ' ' . $months[$date->month - 1] . ' ' . $date->year;
    }

    /**
     * Data kartu seperti yang dilihat peserta lain.
     * Isian yang disembunyikan TIDAK ikut dikirim ke browser sama sekali.
     */
    public function directoryCard(): array
    {
        $vis = $this->cardVisibility();
        $words = array_slice(array_filter(preg_split('/\s+/', trim((string) $this->name))), 0, 2);
        $initials = '';
        foreach ($words as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        $start = self::idDate($this->internship_start_date);
        $end = self::idDate($this->internship_end_date);

        $candidates = [
            'division' => optional($this->division)->name,
            'mentor' => optional($this->mentor)->name,
            'major' => $this->major,
            'institution' => $this->institution,
            'period' => ($start || $end) ? trim(($start ?: '?') . ' – ' . ($end ?: '?')) : null,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'birth_date' => self::idDate($this->birth_date),
        ];

        $rows = [];
        foreach ($candidates as $key => $value) {
            if (! empty($vis[$key]) && filled($value)) {
                $rows[] = ['k' => $key, 'label' => self::CARD_FIELDS[$key], 'value' => (string) $value];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $initials ?: '-',
            'photo' => ($vis['photo'] && $this->profile_photo_path) ? asset('storage/' . $this->profile_photo_path) : null,
            'rows' => $rows,
        ];
    }

    public function getRouteKeyName()
    {
        return 'name';
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users', 'user_id', 'role_id');
    }

    /** Cache slug role per instance agar tidak query berulang. */
    protected $cachedRoleSlugs = null;

    public function roleSlugs(): array
    {
        if ($this->cachedRoleSlugs === null) {
            $this->cachedRoleSlugs = $this->roles()->pluck('slug')->all();
        }

        return $this->cachedRoleSlugs;
    }

    public function hasRole(string $role): bool
    {
        $slugs = $this->roleSlugs();

        if (in_array($role, $slugs, true)) {
            return true;
        }

        // Super admin otomatis memiliki hak akses admin.
        return $role === 'admin' && in_array('super_admin', $slugs, true);
    }

    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /** Mentor murni (bukan admin). Data dibatasi ke divisinya. */
    public function isMentor(): bool
    {
        return $this->hasRole('mentor') && ! $this->hasRole('admin');
    }

    /** Batasi query peserta: mentor hanya melihat peserta di divisinya, admin melihat semua. */
    public function scopeInScope($query, ?self $viewer = null)
    {
        $viewer = $viewer ?: auth()->user();

        if ($viewer && $viewer->isMentor()) {
            $viewer->division_id
                ? $query->where('users.division_id', $viewer->division_id)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /** True jika $viewer boleh melihat data milik user ini. */
    public function isVisibleTo(?self $viewer): bool
    {
        if (! $viewer) {
            return false;
        }
        if ($viewer->hasRole('admin') || (int) $viewer->id === (int) $this->id) {
            return true;
        }

        return $viewer->isMentor()
            && $viewer->division_id
            && (int) $viewer->division_id === (int) $this->division_id;
    }

    public function isActive(): bool
    {
        return (int) ($this->is_active ?? 1) !== 0;
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function mentor()
    {
        return $this->belongsTo(Mentor::class);
    }

    /** Hanya akun berperan "user" (peserta magang), agar admin tidak ikut terhitung. */
    public function scopePeserta($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('slug', 'user');
        });
    }

    /** Daftar peserta, urut nama: User::orderByName()->get(). */
    public function scopeOrderByName($query, string $direction = 'asc')
    {
        return $query->peserta()->orderBy('name', $direction);
    }

    /** Apakah akun ini sudah punya data absensi/izin/laporan. */
    public function hasRecords(): bool
    {
        return $this->attendance()->exists()
            || $this->leave()->exists()
            || $this->absenceRequests()->exists()
            || $this->finalReports()->exists()
            || $this->monthlyReports()->exists()
            || AttendanceDraftDocument::where('user_id', $this->getKey())->exists();
    }

    /**
     * Hapus seluruh data kehadiran dan laporan akun ini (akunnya tidak dihapus).
     * Mengembalikan daftar file (disk public) yang harus dihapus setelah transaksi berhasil.
     */
    public function purgeRecords(): array
    {
        $id = $this->getKey();

        $files = collect()
            ->merge(FinalReport::where('user_id', $id)->pluck('file_path'))
            ->merge(MonthlyReport::where('user_id', $id)->pluck('file_path'))
            ->merge(AttendanceDraftDocument::where('user_id', $id)->pluck('file_path'))
            ->merge(AbsenceRequest::where('user_id', $id)->pluck('document_path'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            Attendance::where('user_id', $id)->delete();
            Leave::where('user_id', $id)->delete();
            AbsenceRequest::where('user_id', $id)->delete();
            FinalReport::where('user_id', $id)->delete();
            MonthlyReport::where('user_id', $id)->delete();
            AttendanceDraftDocument::where('user_id', $id)->delete();

            $this->schedules()->detach();
        });

        return $files;
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    public function leave()
    {
        return $this->hasMany(Leave::class, 'user_id');
    }

    public function finalReports()
    {
        return $this->hasMany(FinalReport::class, 'user_id');
    }

    public function monthlyReports()
    {
        return $this->hasMany(MonthlyReport::class, 'user_id');
    }

    public function absenceRequests()
    {
        return $this->hasMany(AbsenceRequest::class, 'user_id');
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_user', 'user_id', 'schedule_id');
    }
}
?>
