<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkTask extends Model
{
    public const TYPE_PROJECT = 'project';
    public const TYPE_DAILY = 'daily';

    public const CATEGORIES = [
        'input_data' => 'Input Data',
        'validasi' => 'Penyusunan & Validasi Data',
        'administrasi' => 'Administrasi',
        'lainnya' => 'Lainnya',
    ];

    public const WORK_STATUSES = [
        'done' => 'Selesai',
        'progress' => 'Dalam Proses',
        'blocked' => 'Terkendala',
    ];

    protected $fillable = [
        'type',
        'code',
        'category',
        'quantity',
        'quantity_unit',
        'work_status',
        'title',
        'description',
        'deadline',
        'worked_on',
        'start_date',
        'end_date',
        'require_attachment',
        'reference_link',
        'reference_file_path',
        'reference_file_name',
        'is_self',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'worked_on' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'require_attachment' => 'boolean',
        'is_self' => 'boolean',
        'quantity' => 'integer',
    ];

    public function isDaily(): bool
    {
        return $this->type === self::TYPE_DAILY;
    }

    public function categoryLabel(): ?string
    {
        return self::CATEGORIES[$this->category] ?? null;
    }

    public function workStatusLabel(): ?string
    {
        return self::WORK_STATUSES[$this->work_status] ?? null;
    }

    /** Divisi untuk id tugas: divisi pembuat; bila kosong (mis. admin) divisi peserta pertama. */
    public static function divisionIdFor($creatorId, $taskId = null, array $userIds = []): ?int
    {
        $div = DB::table('users')->where('id', $creatorId)->value('division_id');

        if (! $div && $taskId) {
            $div = DB::table('work_task_assignees')
                ->join('users', 'users.id', '=', 'work_task_assignees.user_id')
                ->where('work_task_assignees.work_task_id', $taskId)
                ->whereNotNull('users.division_id')->orderBy('work_task_assignees.id')->value('users.division_id');
        }
        if (! $div && $userIds) {
            $div = DB::table('users')->whereIn('id', $userIds)->whereNotNull('division_id')->orderBy('id')->value('division_id');
        }

        return $div ? (int) $div : null;
    }

    /**
     * Id tugas unik per bentuk tugas + divisi: PRJ-<DIV>-0001 (proyek) / HRN-<DIV>-0001 (harian).
     * Panggil di dalam transaksi agar nomor urut aman.
     */
    public static function generateCode(string $type, ?int $divisionId): string
    {
        $abbr = 'UMM';
        if ($divisionId && ($div = Division::find($divisionId))) {
            $abbr = $div->codeAbbr();
        }

        $prefix = ($type === self::TYPE_DAILY ? 'HRN' : 'PRJ') . '-' . $abbr . '-';

        $last = DB::table('work_tasks')->where('code', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('code')->value('code');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees()
    {
        return $this->hasMany(WorkTaskAssignee::class);
    }

    public function messages()
    {
        return $this->hasMany(WorkTaskMessage::class)->orderBy('id');
    }

    public function references()
    {
        return $this->hasMany(WorkTaskReference::class)->orderBy('id');
    }

    public function partnerRequests()
    {
        return $this->hasMany(WorkTaskPartnerRequest::class);
    }

    /** Setiap pengeditan -> semua peserta kembali "belum". Lampiran peserta tetap tersimpan. */
    /**
     * Setiap pengeditan: status seluruh peserta kembali "belum" (lampiran tetap).
     * Peserta yang sudah dibatalkan tetap batal; hanya mentor yang bisa mengaktifkannya kembali.
     * Mengembalikan daftar perubahan status (untuk riwayat): [['name' => ..., 'old' => ..., 'new' => 'belum'], ...].
     */
    public function resetAssigneeStatuses(): array
    {
        $moves = [];
        foreach ($this->assignees()->with('user')->get() as $a) {   // data terbaru (peserta bisa baru ditambah/dikeluarkan)
            if (! in_array($a->status, [WorkTaskAssignee::MENUNGGU, WorkTaskAssignee::SUDAH], true)) {
                continue;
            }
            $moves[] = ['name' => optional($a->user)->name, 'old' => $a->status, 'new' => WorkTaskAssignee::BELUM];
        }

        $this->assignees()->where('status', '!=', WorkTaskAssignee::BATAL)->update([
            'status' => WorkTaskAssignee::BELUM,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_note' => null,
        ]);
        $this->unsetRelation('assignees');

        return $moves;
    }

    /** Tugas dibatalkan bila ada peserta dan seluruhnya berstatus batal. */
    public function isCancelled(): bool
    {
        $this->loadMissing('assignees');

        return $this->assignees->isNotEmpty() && $this->assignees->every(function ($a) {
            return $a->status === WorkTaskAssignee::BATAL;
        });
    }

    /**
     * Batalkan tugas untuk peserta yang diberikan (yang belum batal). Lampiran tetap tersimpan.
     * Mengembalikan daftar perubahan status (untuk riwayat).
     */
    public function cancelAssignees($assignees, ?User $by = null, ?string $note = null): array
    {
        $moves = [];
        foreach ($assignees as $a) {
            if ($a->status === WorkTaskAssignee::BATAL) {
                continue;
            }
            $moves[] = ['name' => optional($a->user)->name, 'old' => $a->status, 'new' => WorkTaskAssignee::BATAL];
            $a->update([
                'status' => WorkTaskAssignee::BATAL,
                'reviewed_at' => $by ? now() : $a->reviewed_at,
                'reviewed_by' => $by ? $by->id : $a->reviewed_by,
                'review_note' => $note ?: $a->review_note,
            ]);
        }
        $this->unsetRelation('assignees');

        return $moves;
    }

    /** Tanggal mulai tugas; untuk data lama (kosong) memakai tanggal dikerjakan atau tanggal dibuat. */
    public function workedDate(): Carbon
    {
        return $this->start_date ?: ($this->worked_on ?: $this->created_at);
    }

    /** Tanggal selesai tugas (tenggat untuk tugas mentor); null bila data lama tanpa tenggat. */
    public function endDate(): ?Carbon
    {
        return $this->end_date ?: ($this->deadline ?: $this->worked_on);
    }

    /** Teks rentang tanggal, mis. "02 Nov 2026 – 09 Nov 2026"; satu tanggal bila mulai = selesai. */
    public function dateRangeText(string $format = 'd M Y'): string
    {
        $start = $this->workedDate()->copy()->locale('id');
        $end = $this->endDate();

        if (! $end) {
            return $start->translatedFormat($format) . ' – Tanpa tenggat';
        }

        $end = $end->copy()->locale('id');

        return $start->isSameDay($end)
            ? $start->translatedFormat($format)
            : $start->translatedFormat($format) . ' – ' . $end->translatedFormat($format);
    }

    /** Tenggat berlaku sampai akhir hari (23:59). */
    public function deadlineEnd(): ?Carbon
    {
        return $this->deadline ? $this->deadline->copy()->endOfDay() : null;
    }

    /**
     * Hitung mundur tenggat: > 1 hari -> hari, < 1 hari -> jam, < 1 jam -> menit.
     * null jika tanpa tenggat atau sudah lewat.
     */
    public function deadlineCountdown(): ?array
    {
        $end = $this->deadlineEnd();

        if (! $end || now()->gte($end)) {
            return null;
        }

        $minutes = now()->diffInMinutes($end);

        if ($minutes >= 1440) {
            return ['text' => intdiv($minutes, 1440) . ' hari lagi', 'urgent' => false];
        }
        if ($minutes >= 60) {
            return ['text' => intdiv($minutes, 60) . ' jam lagi', 'urgent' => true];
        }

        return ['text' => max(1, $minutes) . ' menit lagi', 'urgent' => true];
    }

    public function isPastDeadline(): bool
    {
        return $this->deadline !== null && Carbon::today()->gt($this->deadline);
    }

    /** Cari lewat id tugas, judul, atau deskripsi. */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($w) use ($term) {
            $w->where('work_tasks.code', 'like', '%' . $term . '%')
                ->orWhere('work_tasks.title', 'like', '%' . $term . '%')
                ->orWhere('work_tasks.description', 'like', '%' . $term . '%');
        });
    }

    public static function typeParam($value): string
    {
        return $value === self::TYPE_DAILY ? self::TYPE_DAILY : self::TYPE_PROJECT;
    }

    /** Filter per bulan: pakai tanggal selesai (tenggat), jika kosong pakai tanggal mulai / dibuat. */
    public function scopeInMonth($query, string $month)
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();

        return $query->whereRaw(
            'COALESCE(work_tasks.end_date, work_tasks.deadline, work_tasks.worked_on, work_tasks.start_date, DATE(work_tasks.created_at)) BETWEEN ? AND ?',
            [$start, $end]
        );
    }

    /** Admin & super admin: semua. Mentor: tugas buatannya + tugas yang dicatat peserta divisinya. Peserta: tugas miliknya. */
    public function scopeVisibleTo($query, User $viewer)
    {
        if ($viewer->hasRole('admin')) {
            return $query;
        }

        if ($viewer->isMentor()) {
            // Tugas buatannya sendiri + tugas yang dicatat peserta divisinya (bukan buatan admin/mentor lain).
            return $query->where(function ($w) use ($viewer) {
                $w->where('work_tasks.created_by', $viewer->id)
                    ->orWhere(function ($self) use ($viewer) {
                        $self->where('work_tasks.is_self', true)
                            ->whereHas('assignees.user', function ($u) use ($viewer) {
                                $u->where('users.division_id', $viewer->division_id ?: 0);
                            });
                    });
            });
        }

        // Viewer: semua tugas yang dikerjakan peserta di divisinya (hanya-baca).
        if ($viewer->isViewer()) {
            return $query->whereHas('assignees.user', function ($u) use ($viewer) {
                $u->where('users.division_id', $viewer->division_id ?: 0);
            });
        }

        // Peserta: tugas yang dibatalkan tidak muncul (hanya mentor/admin yang bisa mengaktifkannya kembali).
        return $query->whereHas('assignees', function ($a) use ($viewer) {
            $a->where('user_id', $viewer->id)->where('status', '!=', WorkTaskAssignee::BATAL);
        });
    }

    public function isVisibleTo(?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if ($viewer->hasRole('admin')) {
            return true;
        }

        $this->loadMissing('assignees.user');

        if ($viewer->isViewer()) {
            return $this->assigneesFor($viewer)->isNotEmpty();
        }

        if ($viewer->isMentor()) {
            return (int) $this->created_by === (int) $viewer->id
                || ($this->is_self && $this->assigneesFor($viewer)->isNotEmpty());
        }

        return $this->hasAssignee($viewer);
    }

    /** Peserta yang boleh dilihat viewer (mentor: hanya divisinya). */
    public function assigneesFor(User $viewer)
    {
        $this->loadMissing('assignees.user.division');

        if ($viewer->isMentor() || $viewer->isViewer()) {
            return $this->assignees->filter(function ($a) use ($viewer) {
                return $a->user && $viewer->division_id
                    && (int) $a->user->division_id === (int) $viewer->division_id;
            })->values();
        }

        return $this->assignees->values();
    }

    /** Peserta ini terdaftar sebagai pengerja tugas yang aktif (pembuat maupun rekan kolaborasi; yang sudah dibatalkan tidak dihitung). */
    public function hasAssignee(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $this->loadMissing('assignees');

        return $this->assignees->contains(function ($a) use ($user) {
            return (int) $a->user_id === (int) $user->id && $a->status !== WorkTaskAssignee::BATAL;
        });
    }

    /**
     * Admin / mentor terkait: boleh mengedit kapan saja (termasuk tugas yang sudah lewat tenggat dan tugas yang dicatat peserta).
     * Peserta: hanya tugas yang dicatat sendiri (pembuat dan rekan kolaborasi yang sudah menerima ajakan), dan sebelum tenggat.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if ($user && $user->isViewer()) {
            return false;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin') || $user->isMentor()) {
            return $this->isVisibleTo($user);
        }

        if (! $this->is_self || $this->isPastDeadline()) {
            return false;
        }

        return (int) $this->created_by === (int) $user->id || $this->hasAssignee($user);
    }

    /**
     * Admin / mentor terkait: boleh membatalkan selama masih ada peserta (yang dikelolanya) yang belum batal.
     * Peserta: hanya tugas yang dicatat sendiri (pembuat atau rekan kolaborasi), dan belum disetujui mentor.
     */
    public function canBeCancelledBy(?User $user): bool
    {
        if ($user && $user->isViewer()) {
            return false;
        }

        if (! $user || ! $this->isVisibleTo($user)) {
            return false;
        }

        if ($user->hasRole('admin') || $user->isMentor()) {
            return $this->assigneesFor($user)->contains(function ($a) {
                return $a->status !== WorkTaskAssignee::BATAL;
            });
        }

        if (! $this->is_self || ! $this->hasAssignee($user)) {
            return false;
        }

        $mine = $this->assignees->firstWhere('user_id', $user->id);

        return $mine && $mine->status !== WorkTaskAssignee::SUDAH;
    }

    /**
     * Boleh menambah referensi materi dari halaman detail:
     * admin / mentor yang dapat melihat tugas ini, atau peserta yang aktif mengerjakannya.
     */
    public function canAddReferencesBy(?User $user): bool
    {
        if ($user && $user->isViewer()) {
            return false;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin') || $user->isMentor()) {
            return $this->isVisibleTo($user);
        }

        return $this->hasAssignee($user);
    }

    public static function initials(?string $name): string
    {
        $out = '';
        foreach (array_slice(array_filter(preg_split('/\s+/', trim((string) $name))), 0, 2) as $word) {
            $out .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $out ?: '?';
    }

    public static function period(User $person): string
    {
        if (! $person->internship_start_date || ! $person->internship_end_date) {
            return '-';
        }

        $s = Carbon::parse($person->internship_start_date)->locale('id');
        $e = Carbon::parse($person->internship_end_date)->locale('id');

        return $s->year === $e->year
            ? $s->translatedFormat('M') . ' – ' . $e->translatedFormat('M Y')
            : $s->translatedFormat('M Y') . ' – ' . $e->translatedFormat('M Y');
    }

    /**
     * Data kartu peserta (dipakai kartu putar people-card).
     * Peserta melihat hanya informasi yang diizinkan pemilik kartu; mentor/admin melihat 5 informasi lengkap.
     */
    public static function cardPayload(User $person, ?User $viewer = null): array
    {
        $viewer = $viewer ?: auth()->user();
        $full = $viewer && ($viewer->hasRole('admin') || $viewer->isMentor());

        $card = $person->directoryCard($full);

        $division = '';
        foreach ($card['rows'] as $row) {
            if ($row['k'] === 'division') {
                $division = $row['value'];
            }
        }

        // 'division' dan 'tone' hanya untuk ikon kecil + tooltip di footer kartu tugas.
        return $card + ['division' => $division, 'tone' => abs(crc32((string) $person->name)) % 5];
    }
}
