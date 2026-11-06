<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AbsenceRequest extends Model
{
    /** Batas panjang rentang izin/sakit (hari) pada satu pengajuan. */
    public const MAX_RANGE_DAYS = 31;

    protected $fillable = [
        'user_id',
        'absence_date',
        'absence_end_date',
        'absence_type',
        'correction_time_in',
        'correction_time_out',
        'original_time_in',
        'original_time_out',
        'correction_applied_at',
        'reason',
        'document_name',
        'document_path',
        'document_size',
        'document_mime',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'absence_date' => 'date',
        'absence_end_date' => 'date',
        'correction_applied_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Pengajuan yang rentang tanggalnya beririsan dengan [$start, $end] (format Y-m-d).
     * Pengajuan lama tanpa absence_end_date dianggap 1 hari.
     */
    public function scopeOverlapping($query, string $start, string $end)
    {
        return $query
            ->whereDate('absence_date', '<=', $end)
            ->whereRaw('DATE(COALESCE(absence_end_date, absence_date)) >= ?', [$start]);
    }

    /** Tanggal selesai; jika kosong (data lama) sama dengan tanggal mulai. */
    public function getEndDateAttribute()
    {
        return $this->absence_end_date ?: $this->absence_date;
    }

    public function getIsRangeAttribute(): bool
    {
        return $this->absence_date
            && $this->end_date
            && $this->absence_date->toDateString() !== $this->end_date->toDateString();
    }

    public function getDaysCountAttribute(): int
    {
        if (!$this->absence_date || !$this->end_date) {
            return 1;
        }

        return $this->absence_date->copy()->startOfDay()->diffInDays($this->end_date->copy()->startOfDay()) + 1;
    }

    /** Contoh: "14 Okt 2026", "13 - 15 Okt 2026", "30 Okt - 2 Nov 2026". */
    public function getDateRangeLabelAttribute(): string
    {
        if (!$this->absence_date) {
            return '-';
        }

        $start = $this->absence_date->copy()->locale('id');
        if (!$this->is_range) {
            return $start->translatedFormat('j M Y');
        }

        $end = $this->end_date->copy()->locale('id');
        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->translatedFormat('j') . ' - ' . $end->translatedFormat('j M Y');
        }
        if ($start->format('Y') === $end->format('Y')) {
            return $start->translatedFormat('j M') . ' - ' . $end->translatedFormat('j M Y');
        }

        return $start->translatedFormat('j M Y') . ' - ' . $end->translatedFormat('j M Y');
    }

    /**
     * Petakan pengajuan ke setiap tanggal yang dicakupnya dalam rentang [$start, $end].
     * Hasil: koleksi [Y-m-d => AbsenceRequest]; bila beberapa pengajuan jatuh di tanggal yang
     * sama, yang paling baru ditinjau/diajukan dipakai.
     *
     * Rentang izin/sakit lebih dari satu hari TIDAK menandai akhir pekan dan hari libur
     * nasional ($nonWorkingDates = [Y-m-d => kode]) karena itu bukan hari kerja.
     */
    public static function dateMapFor(
        int $userId,
        Carbon $start,
        Carbon $end,
        array $statuses = ['approved', 'rejected'],
        array $nonWorkingDates = []
    ) {
        $requests = static::where('user_id', $userId)
            ->overlapping($start->toDateString(), $end->toDateString())
            ->whereIn('status', $statuses)
            ->orderBy('reviewed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $windowStart = $start->copy()->startOfDay();
        $windowEnd = $end->copy()->startOfDay();
        $map = collect();

        foreach ($requests as $request) {
            $from = Carbon::parse($request->absence_date)->startOfDay();
            $to = Carbon::parse($request->end_date)->startOfDay();
            $isRange = $from->lt($to);
            $skipNonWorking = $isRange && $request->absence_type !== 'lupa_absensi';

            $cursor = $from->lt($windowStart) ? $windowStart->copy() : $from->copy();
            $limit = $to->gt($windowEnd) ? $windowEnd->copy() : $to->copy();

            for (; $cursor->lte($limit); $cursor->addDay()) {
                $key = $cursor->toDateString();
                if ($skipNonWorking && ($cursor->isWeekend() || isset($nonWorkingDates[$key]))) {
                    continue;
                }
                if (!$map->has($key)) {
                    $map->put($key, $request);
                }
            }
        }

        return $map;
    }

    /**
     * Jumlah hari sakit, izin, dan tanpa keterangan seluruh peserta dalam [$start, $end].
     * Aturan sama dengan dateMapFor() yang dipakai Sheet Report:
     * - approved + sakit  -> sakit; approved lainnya (bukan lupa_absensi) -> izin
     * - rejected          -> tanpa_keterangan
     * - lupa_absensi yang disetujui adalah koreksi jam, bukan ketidakhadiran (tidak dihitung)
     * - satu peserta dihitung sekali per tanggal; rentang >1 hari melewati akhir pekan/libur nasional.
     */
    public static function dayCountsFor(Carbon $start, Carbon $end, array $nonWorkingDates = [], ?array $userIds = null): array
    {
        $requests = static::overlapping($start->toDateString(), $end->toDateString())
            ->whereIn('status', ['approved', 'rejected'])
            ->when($userIds !== null, fn ($q) => $q->whereIn('user_id', $userIds))
            ->orderBy('reviewed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $windowStart = $start->copy()->startOfDay();
        $windowEnd = $end->copy()->startOfDay();
        $seen = [];
        $counts = ['sakit' => 0, 'izin' => 0, 'tanpa_keterangan' => 0];

        foreach ($requests as $request) {
            $from = Carbon::parse($request->absence_date)->startOfDay();
            $to = Carbon::parse($request->end_date)->startOfDay();
            $type = strtolower((string) ($request->absence_type ?: 'izin'));
            $skipNonWorking = $from->lt($to) && $type !== 'lupa_absensi';

            $cursor = $from->lt($windowStart) ? $windowStart->copy() : $from->copy();
            $limit = $to->gt($windowEnd) ? $windowEnd->copy() : $to->copy();

            for (; $cursor->lte($limit); $cursor->addDay()) {
                $date = $cursor->toDateString();
                if ($skipNonWorking && ($cursor->isWeekend() || isset($nonWorkingDates[$date]))) {
                    continue;
                }

                $key = $request->user_id . '|' . $date;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if ($request->status === 'rejected') {
                    $counts['tanpa_keterangan']++;
                } elseif ($type === 'lupa_absensi') {
                    continue;
                } elseif ($type === 'sakit') {
                    $counts['sakit']++;
                } else {
                    $counts['izin']++;
                }
            }
        }

        return $counts;
    }
}
