<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WorkTask extends Model
{
    protected $fillable = [
        'title',
        'description',
        'deadline',
        'worked_on',
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
        'require_attachment' => 'boolean',
        'is_self' => 'boolean',
    ];

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
    public function resetAssigneeStatuses(): void
    {
        $this->assignees()->update([
            'status' => WorkTaskAssignee::BELUM,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_note' => null,
        ]);
    }

    /** Tanggal pekerjaan dikerjakan; untuk data lama (kosong) memakai tanggal dibuat. */
    public function workedDate(): Carbon
    {
        return $this->worked_on ?: $this->created_at;
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

    /** Filter per bulan: pakai tenggat, jika kosong pakai tanggal dibuat. */
    public function scopeInMonth($query, string $month)
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();

        return $query->whereRaw(
            'COALESCE(work_tasks.deadline, work_tasks.worked_on, DATE(work_tasks.created_at)) BETWEEN ? AND ?',
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

        return $query->whereHas('assignees', function ($a) use ($viewer) {
            $a->where('user_id', $viewer->id);
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

        if ($viewer->isMentor()) {
            return (int) $this->created_by === (int) $viewer->id
                || ($this->is_self && $this->assigneesFor($viewer)->isNotEmpty());
        }

        return $this->assignees->contains('user_id', $viewer->id);
    }

    /** Peserta yang boleh dilihat viewer (mentor: hanya divisinya). */
    public function assigneesFor(User $viewer)
    {
        $this->loadMissing('assignees.user.division');

        if ($viewer->isMentor()) {
            return $this->assignees->filter(function ($a) use ($viewer) {
                return $a->user && $viewer->division_id
                    && (int) $a->user->division_id === (int) $viewer->division_id;
            })->values();
        }

        return $this->assignees->values();
    }

    /**
     * Tugas dibuat peserta -> hanya pembuat yang boleh edit.
     * Tugas dibuat mentor/admin -> admin atau mentor terkait. Semua hanya sebelum tenggat.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (! $user || $this->isPastDeadline()) {
            return false;
        }

        if ($this->is_self) {
            return (int) $this->created_by === (int) $user->id;
        }

        return ($user->hasRole('admin') || $user->isMentor()) && $this->isVisibleTo($user);
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
