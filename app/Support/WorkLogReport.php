<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskProgress;
use Carbon\Carbon;

/**
 * Export hasil pekerjaan peserta ke dokumen Word (mengikuti templat "Log Pekerjaan Portfolio").
 * - collect(): SELURUH pekerjaan peserta yang berstatus SUDAH selesai (tanpa batas tanggal).
 * - render():  periode (hari / minggu / bulan) hanya menentukan cara pengelompokan tanggal pada dokumen.
 */
class WorkLogReport
{
    /** Nilai pilihan untuk tugas yang tidak punya kategori. */
    public const NO_CATEGORY = '__none__';

    public const TYPES = [
        'project' => 'Tugas proyek',
        'daily' => 'Tugas harian',
        'both' => 'Tugas proyek & tugas harian',
    ];

    /** Format pengelompokan tanggal pada dokumen. */
    public const PERIODS = [
        'day' => 'Per hari',
        'week' => 'Per minggu',
        'month' => 'Per bulan',
    ];

    /**
     * Kategori tugas harian yang pernah dikerjakan peserta (status sudah selesai).
     * Mengembalikan daftar nama kategori (urut abjad), ditambah NO_CATEGORY bila ada tugas tanpa kategori.
     */
    public static function usedCategories(User $user): array
    {
        $names = WorkTaskAssignee::with('task')
            ->where('user_id', $user->id)
            ->where('status', WorkTaskAssignee::SUDAH)
            ->whereHas('task', function ($q) {
                $q->where('type', 'daily');
            })
            ->get()
            ->map(function ($a) {
                return trim((string) optional($a->task)->category);
            });

        $cats = $names->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        if ($names->contains('')) {
            $cats[] = self::NO_CATEGORY;
        }

        return $cats;
    }

    /**
     * Seluruh pekerjaan peserta yang sudah selesai (disetujui mentor).
     * Tugas dengan turunan dicatat per tanggal turunan milik peserta; tugas lain pada tanggal selesainya.
     * Tiap item: ... + date (Y-m-d), quantity, notes = [['date' => Y-m-d, 'text' => string, 'qty' => ?int], ...].
     * Filter kategori hanya berlaku untuk tugas harian; tugas proyek selalu ikut.
     */
    public static function collect(User $user, array $types, array $categories): array
    {
        $assignees = WorkTaskAssignee::with(['task'])
            ->where('user_id', $user->id)
            ->where('status', WorkTaskAssignee::SUDAH)
            ->whereHas('task', function ($q) use ($types) {
                $q->whereIn('type', $types);
            })
            ->get();

        $items = [];

        foreach ($assignees as $a) {
            $task = $a->task;
            if (! $task) {
                continue;
            }

            $category = trim((string) $task->category);
            $key = $category !== '' ? $category : self::NO_CATEGORY;
            if ($task->type === 'daily' && ! in_array($key, $categories, true)) {
                continue;
            }

            $base = [
                'task_id' => $task->id,
                'code' => (string) $task->code,
                'type' => $task->type,
                'title' => (string) $task->title,
                'description' => trim((string) $task->description),
                'category' => $category !== '' ? $category : null,
                'unit' => trim((string) $task->quantity_unit),
            ];

            $entries = WorkTaskProgress::where('work_task_id', $task->id)
                ->where('user_id', $user->id)
                ->orderBy('entry_date')->orderBy('id')->get();

            if ($task->has_children) {
                foreach ($entries->groupBy(function ($e) {
                    return $e->entry_date->toDateString();
                }) as $date => $list) {
                    $qty = $list->whereNotNull('quantity')->isNotEmpty() ? (int) $list->sum('quantity') : null;
                    $notes = [];
                    foreach ($list as $e) {
                        if (trim((string) $e->note) !== '') {
                            $notes[] = ['date' => $date, 'text' => trim((string) $e->note), 'qty' => $e->quantity !== null ? (int) $e->quantity : null];
                        }
                    }

                    $items[] = $base + ['date' => $date, 'quantity' => $qty, 'notes' => $notes];
                }

                continue;
            }

            $day = ($task->endDate() ?: $task->workedDate())->copy()->startOfDay();
            $notes = [];
            if ($task->type === 'project') {
                foreach ($entries as $e) {
                    if (trim((string) $e->note) !== '') {
                        $notes[] = ['date' => $e->entry_date->toDateString(), 'text' => trim((string) $e->note), 'qty' => $e->quantity !== null ? (int) $e->quantity : null];
                    }
                }
            }

            $items[] = $base + ['date' => $day->toDateString(), 'quantity' => $task->quantity, 'notes' => $notes];
        }

        return $items;
    }

    /** Kelompok tanggal: [kunci urut, teks kolom Tanggal]. Hari = satu tanggal; minggu = Senin–Minggu; bulan = tanggal 1–akhir bulan. */
    private static function bucket(string $period, string $date): array
    {
        $d = Carbon::createFromFormat('!Y-m-d', $date);

        if ($period === 'day') {
            return [$date, $d->format('d/m/Y')];
        }

        if ($period === 'week') {
            $s = $d->copy()->startOfWeek(Carbon::MONDAY);
            $e = $s->copy()->addDays(6);
        } else {
            $s = $d->copy()->startOfMonth();
            $e = $d->copy()->endOfMonth();
        }

        return [$s->toDateString(), $s->format('d/m/Y') . " –\n" . $e->format('d/m/Y')];
    }

    /** Kelompokkan item per kelompok tanggal; di tiap kelompok, item tugas yang sama digabung. */
    private static function group(array $items, string $period): array
    {
        $g = [];
        foreach ($items as $it) {
            [$key, $label] = self::bucket($period, $it['date']);
            $g[$key]['label'] = $label;
            $g[$key]['items'][] = $it;
        }
        ksort($g);
        foreach ($g as $k => $v) {
            $g[$k]['items'] = self::mergeByTask($v['items']);
        }

        return $g;
    }

    /** Gabungkan item satu tugas yang tercatat di beberapa tanggal menjadi satu; urut tanggal terawal lalu ID. */
    private static function mergeByTask(array $items): array
    {
        $out = [];
        foreach ($items as $it) {
            $id = $it['task_id'];
            if (! isset($out[$id])) {
                $out[$id] = $it;
                continue;
            }
            $m = &$out[$id];
            if ($it['quantity'] !== null) {
                $m['quantity'] = ($m['quantity'] ?? 0) + (int) $it['quantity'];
            }
            $m['notes'] = array_merge($m['notes'], $it['notes']);
            if ($it['date'] < $m['date']) {
                $m['date'] = $it['date'];
            }
            unset($m);
        }
        $out = array_values($out);
        foreach ($out as $i => $o) {
            usort($o['notes'], function ($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            $out[$i] = $o;
        }
        usort($out, function ($a, $b) {
            return [$a['date'], $a['code']] <=> [$b['date'], $b['code']];
        });

        return $out;
    }

    /** Isi sel "Rincian Pekerjaan": nomor + [ID] + judul, deskripsi, lalu catatan per poin (tanggal, isi, output). */
    private static function detailParas(array $tasks): array
    {
        $p = [];
        foreach ($tasks as $i => $it) {
            $tag = $it['code'] !== '' ? '[' . $it['code'] . '] ' : '';
            $p[] = ['runs' => [
                ['text' => ($i + 1) . '. ' . $tag, 'b' => true],
                ['text' => $it['title'], 'b' => true],
            ], 'before' => $i > 0 ? 80 : 0, 'keepNext' => false];

            if ($it['description'] !== '') {
                $p[] = ['text' => mb_strimwidth($it['description'], 0, 300, '…'), 'sz' => 15, 'indent' => 280];
            }
            foreach ($it['notes'] as $n) {
                $line = '• ' . Carbon::createFromFormat('!Y-m-d', $n['date'])->format('d/m/Y') . ': ' . mb_strimwidth($n['text'], 0, 300, '…');
                if ($n['qty'] !== null) {
                    $line .= ' (Output: ' . self::qtyText($n['qty'], $it['unit']) . ')';
                }
                $p[] = ['text' => $line, 'sz' => 14, 'color' => '404040', 'indent' => 440, 'hanging' => 160];
            }
        }

        return $p;
    }

    private static function qtyText($qty, string $unit): string
    {
        return number_format((int) $qty, 0, ',', '.') . ($unit !== '' ? ' ' . $unit : '');
    }

    /** Total selesai per satuan dari sekumpulan item. Mengembalikan [satuan => total]. */
    private static function unitTotals(array $items): array
    {
        $totals = [];
        foreach ($items as $it) {
            if ($it['quantity'] === null) {
                continue;
            }
            $totals[$it['unit']] = ($totals[$it['unit']] ?? 0) + (int) $it['quantity'];
        }

        return $totals;
    }

    private static function totalsText(array $totals): string
    {
        $parts = [];
        foreach ($totals as $unit => $qty) {
            $parts[] = self::qtyText($qty, (string) $unit);
        }

        return $parts ? implode(', ', $parts) : '-';
    }

    private static function sortCategories(array $keys): array
    {
        usort($keys, function ($a, $b) {
            if ($a === '') {
                return 1;
            }
            if ($b === '') {
                return -1;
            }

            return strcasecmp($a, $b);
        });

        return $keys;
    }

    /**
     * Susun dokumen.
     * $meta: name, institution, division, mentor, kind, period (day|week|month), typeLabel, internship, types, printedAt.
     * $items: keluaran collect().
     */
    public static function render(array $meta, array $items, string $path): void
    {
        $doc = new DocxBuilder('Roboto', 16);
        $dash = function ($v) {
            return filled($v) ? $v : '-';
        };
        $period = $meta['period'];

        // ---- Identitas ----
        //$doc->paragraph(['text' => 'PT. PRIMA LAYANAN NIAGA SUKU CADANG (PLNSC)', 'b' => true, 'sz' => 20]);
        $doc->paragraph('PT. PRIMA LAYANAN NIAGA SUKU CADANG (PLNSC)');
        $doc->paragraph('Nama : ' . $dash($meta['name']));
        $doc->paragraph('Asal Kampus : ' . $dash($meta['institution']));
        $doc->paragraph('Divisi : ' . $dash($meta['division']));
        $doc->paragraph('Mentor : ' . $dash($meta['mentor']));
        $doc->spacer(160);
        $doc->paragraph(['text' => 'DAFTAR PEKERJAAN SELAMA PROGRAM MAGANG DI PT PLN SUKU CADANG', 'b' => true, 'sz' => 20, 'align' => 'center']);
        // $doc->paragraph(['text' => 'Format tanggal: ' . $meta['kind'], 'sz' => 17, 'align' => 'center', 'after' => 120]);

        $projectItems = array_values(array_filter($items, function ($it) {
            return $it['type'] === 'project';
        }));
        $dailyItems = array_values(array_filter($items, function ($it) {
            return $it['type'] === 'daily';
        }));
        $projectCount = count(array_unique(array_column($projectItems, 'task_id')));
        $dailyCount = count(array_unique(array_column($dailyItems, 'task_id')));
        $workDays = [];
        foreach ($items as $it) {
            $workDays[$it['date']] = true;
        }

        $heading = ['b' => true, 'sz' => 19, 'keepNext' => true, 'before' => 160, 'after' => 80];
        $lab = ['b' => true, 'fill' => 'F2F2F2'];
        $mid = function ($text) {
            return ['text' => (string) $text, 'align' => 'center', 'valign' => 'center'];
        };

        // ---- 1. Ringkasan Program Magang ----
        $doc->paragraph('1. Ringkasan Program Magang', $heading);
        $doc->table([2300, 2933, 2300, 2933], [
            ['cells' => [$lab + ['text' => 'Periode Magang'], ['text' => $dash($meta['internship'])], $lab + ['text' => 'Total Tugas Proyek'], ['text' => (string) $projectCount]]],
            ['cells' => [$lab + ['text' => 'Total Hari Kerja Tercatat'], ['text' => count($workDays) . ' hari'], $lab + ['text' => 'Total Tugas Harian'], ['text' => (string) $dailyCount]]],
            ['cells' => [$lab + ['text' => 'Total Seluruh Tugas'], ['text' => (string) ($projectCount + $dailyCount)], $lab + ['text' => 'Total selesai Tugas Harian'], ['text' => self::totalsText(self::unitTotals($dailyItems))]]],
        ]);
        // $doc->paragraph(['text' => 'Hanya pekerjaan yang sudah selesai (disetujui mentor).', 'sz' => 14, 'color' => '595959', 'before' => 60]);

        // ---- 2. Daftar Tugas Proyek ----
        if (in_array('project', $meta['types'], true)) {
            $doc->paragraph('2. Daftar Tugas Proyek', $heading);
            $rows = [['header' => true, 'cells' => ['No', 'ID Tugas', 'Tanggal', 'Rincian Pekerjaan']]];
            $n = 0;
            foreach (self::group($projectItems, $period) as $g) {
                foreach ($g['items'] as $i => $it) {
                    $n++;
                    $detail = self::detailParas([$it]);
                    $detail[0]['runs'][0]['text'] = '';
                    $rows[] = ['cells' => [
                        $mid($n),
                        ['text' => $dash($it['code'])],
                        ['text' => $g['label'], 'align' => 'center', 'vmerge' => $i === 0 ? 'restart' : 'continue'],
                        ['p' => $detail],
                    ]];
                }
            }
            if ($n === 0) {
                $rows[] = ['cells' => [['text' => 'Tidak ada tugas proyek.', 'span' => 4, 'align' => 'center']]];
            }
            $doc->table([500, 1700, 1700, 6566], $rows);
        }

        // ---- 3. Daftar Tugas Harian ----
        if (in_array('daily', $meta['types'], true)) {
            $doc->paragraph('3. Daftar Tugas Harian', $heading);
            $rows = [['header' => true, 'cells' => ['No', 'Tanggal', 'Kategori', 'Rincian Pekerjaan', 'Total selesai']]];
            $n = 0;
            foreach (self::group($dailyItems, $period) as $g) {
                $cats = [];
                foreach ($g['items'] as $it) {
                    $cats[$it['category'] ?? ''][] = $it;
                }
                $first = true;
                foreach (self::sortCategories(array_keys($cats)) as $catKey) {
                    $group = $cats[$catKey];
                    $n++;

                    $outputs = [];
                    foreach (self::unitTotals($group) as $unit => $qty) {
                        $outputs[] = ['text' => self::qtyText($qty, (string) $unit), 'align' => 'center'];
                    }

                    $rows[] = ['cells' => [
                        $mid($n),
                        ['text' => $g['label'], 'align' => 'center', 'vmerge' => $first ? 'restart' : 'continue'],
                        ['text' => $catKey !== '' ? $catKey : 'Tanpa Kategori'],
                        ['p' => self::detailParas($group)],
                        ['p' => $outputs ?: [['text' => '-', 'align' => 'center']], 'valign' => 'center'],
                    ]];
                    $first = false;
                }
            }
            if ($n === 0) {
                $rows[] = ['cells' => [['text' => 'Tidak ada tugas harian.', 'span' => 5, 'align' => 'center']]];
            }
            $doc->table([450, 1500, 1500, 5416, 1600], $rows);

            // ---- 4. Rekapitulasi Output Tugas Harian ----
            if ($dailyItems) {
                $doc->paragraph('4. Rekapitulasi Output Tugas Harian', $heading);

                $recap = [];
                foreach ($dailyItems as $it) {
                    $cat = $it['category'] ?? '';
                    $unit = $it['quantity'] === null ? '' : $it['unit'];
                    $row = &$recap[$cat][$unit];
                    $row['tasks'][$it['task_id']] = true;
                    $row['qty'] = ($row['qty'] ?? 0) + (int) ($it['quantity'] ?? 0);
                    $row['has'] = ($row['has'] ?? false) || $it['quantity'] !== null;
                    unset($row);
                }

                $rows = [['header' => true, 'cells' => ['Kategori', 'Jumlah Aktivitas', 'Total Total selesai', 'Satuan']]];
                foreach (self::sortCategories(array_keys($recap)) as $cat) {
                    foreach ($recap[$cat] as $unit => $r) {
                        $rows[] = ['cells' => [
                            ['text' => $cat !== '' ? $cat : 'Tanpa Kategori'],
                            ['text' => (string) count($r['tasks']), 'align' => 'center'],
                            ['text' => $r['has'] ? number_format($r['qty'], 0, ',', '.') : '-', 'align' => 'center'],
                            ['text' => $unit !== '' ? (string) $unit : '-', 'align' => 'center'],
                        ]];
                    }
                }
                $rows[] = ['cells' => [
                    ['text' => 'Total', 'b' => true],
                    ['text' => (string) $dailyCount, 'align' => 'center', 'b' => true],
                    ['text' => self::totalsText(self::unitTotals($dailyItems)), 'span' => 2, 'align' => 'center', 'b' => true],
                ]];
                $doc->table([4000, 2300, 2300, 1866], $rows);
            }
        }

        // ---- Penutup & tanda tangan ----
        $doc->spacer(200);
        $doc->paragraph(['text' => 'Dokumen ini dicetak otomatis oleh Sistem Pengelolaan Magang PT PLNSC pada ' . $meta['printedAt'], 'i' => true, 'sz' => 14, 'color' => '595959', 'after' => 240]);

        $sign = function ($title, $name) {
            return ['p' => [
                ['text' => $title, 'align' => 'center'],
                ['text' => '', 'after' => 700],
                ['text' => '( ' . $name . ' )', 'b' => true, 'align' => 'center'],
            ]];
        };
        $doc->table([5233, 5233], [['cells' => [$sign('Peserta Magang', $dash($meta['name'])), $sign('Mentor', $dash($meta['mentor']))]]], ['borderless' => true]);

        $doc->save($path);
    }
}
