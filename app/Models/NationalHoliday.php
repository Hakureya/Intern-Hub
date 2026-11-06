<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class NationalHoliday extends Model
{
    protected $fillable = ['holiday_date', 'name', 'type'];

    /**
     * Peta hari libur nasional pada bulan $month (format Y-m): [Y-m-d => kode].
     * Ditambah tanggal dari env NATIONAL_HOLIDAYS (dipisah koma) dengan kode "LH".
     */
    public static function mapForMonth(string $month): array
    {
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $mapped = static::query()
            ->whereBetween('holiday_date', [$start, $end])
            ->get()
            ->mapWithKeys(function ($row) {
                return [Carbon::parse($row->holiday_date)->toDateString() => strtoupper((string) $row->type)];
            })
            ->toArray();

        $raw = (string) env('NATIONAL_HOLIDAYS', '');
        if ($raw !== '') {
            $list = array_filter(array_map('trim', explode(',', $raw)));
            foreach ($list as $date) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strpos($date, $month . '-') === 0) {
                    if (!isset($mapped[$date])) {
                        $mapped[$date] = 'LH';
                    }
                }
            }
        }

        return $mapped;
    }
}
