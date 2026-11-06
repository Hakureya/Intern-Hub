@extends('layouts.master')

@section('css')
    <style>
        .presence-wrap {
            background: #f8fafc;
            border: 1px solid #e7ecf2;
            border-radius: 14px;
            overflow: hidden;
        }

        .presence-header {
            background: linear-gradient(90deg, #1a8ca8, #294f97);
            color: #fff;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 18px;
            line-height: 1;
        }

        .presence-body {
            padding: 12px;
        }

        .presence-filter {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 14px;
        }

        .presence-filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .presence-filter .form-control {
            max-width: 200px;
            height: 34px;
            border-radius: 10px;
        }

        .presence-filter .btn {
            height: 34px;
            padding: 6px 12px;
            font-size: 13px;
        }

        .presence-calendar {
            border: 1px solid #dfe5ec;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .presence-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .presence-day-name {
            text-align: center;
            padding: 10px 8px;
            background: #cfe3ef;
            color: #1f4f70;
            font-weight: 700;
            font-size: 12px;
            border-right: 1px solid #d9e5ed;
        }

        .presence-day-name:last-child {
            border-right: 0;
        }

        .presence-cell {
            min-height: 92px;
            padding: 8px 8px 6px;
            border-right: 1px solid #eef2f6;
            border-top: 1px solid #eef2f6;
            background: #fff;
        }

        .presence-cell:nth-child(7n) {
            border-right: 0;
        }

        .presence-cell.muted {
            background: #fafbfd;
            color: #b0b7c3;
        }

        .presence-cell.holiday {
            background: #b7f4ec;
        }

        .presence-date {
            text-align: right;
            font-size: 14px;
            font-weight: 600;
            color: #566173;
            margin-bottom: 8px;
        }

        .presence-chip {
            border-radius: 999px;
            padding: 4px 10px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #2d3748;
            max-width: 140px;
            margin: 0 auto;
            white-space: pre-line;
            line-height: 1.2;
        }

        .chip-hadir {
            background: #a6d8b0;
        }

        .chip-dinas {
            background: #b8d8f0;
        }

        .chip-cuti {
            background: #efb1ad;
        }

        .chip-sakit {
            background: #f0d79f;
        }

        .chip-izin {
            background: #b8d6ec;
        }

        .chip-lupa {
            background: #d6d9de;
        }

        .chip-terlambat {
            background: #f8b4a0;
        }

        .presence-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 10px;
            font-size: 12px;
            color: #4b5563;
        }

        .presence-legend span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .presence-legend i {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>
@endsection

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');
        $monthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $selectedMonth . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(\Carbon\CarbonInterface::SATURDAY);
        $days = [];
        for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
            $days[] = $cursor->copy();
        }
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $monthLabel = $monthStart->translatedFormat('F Y');
        $monthlyRowsByDate = collect($monthlyRows ?? [])->keyBy('date');
    @endphp

    <div class="presence-wrap">
        <div class="presence-header">Presensi</div>
        <div class="presence-body">
            <form method="GET" action="{{ route('sheet-report') }}" class="presence-filter">
                <div class="presence-filter-row">
                    <input class="form-control" type="month" name="month" value="{{ $selectedMonth }}">
                    <select class="form-control" name="user_id" required>
                        <option value="">{{ __('global.pleaseSelect') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ (string) $selectedUserId === (string) $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                </div>
                <div class="presence-filter-row">
                    @if ($selectedUser)
                        <button type="button" class="btn btn-primary btn-sm" id="export-pdf-btn">
                            Ekspor PDF
                        </button>
                    @endif
                </div>
            </form>

            @if ($selectedUser)
                <div class="mb-2 text-muted">
                    <strong>{{ $selectedUser->name }}</strong> - {{ $monthLabel }}
                </div>
                <div class="presence-filter-row mb-2">
                    <span class="badge badge-primary">Hari Kerja: {{ $workingDays }}</span>
                    <span class="badge badge-success">Jumlah Kehadiran: {{ $attendanceDays }}</span>
                </div>

                <div class="presence-calendar">
                    <div class="presence-grid">
                        @foreach ($dayNames as $dayName)
                            <div class="presence-day-name">{{ $dayName }}</div>
                        @endforeach
                    </div>

                    <div class="presence-grid">
                        @foreach ($days as $day)
                            @php
                                $dateKey = $day->format('Y-m-d');
                                $isCurrentMonth = $day->month === $monthStart->month;
                                $isWeekend = $day->isWeekend();
                                $isNationalHoliday = isset(($nationalHolidays ?? [])[$dateKey]);
                                $row = $monthlyRowsByDate->get($dateKey);
                                $timeIn = $row['time_in'] ?? '-';
                                $timeOut = $row['time_out'] ?? '-';
                                $hasTime = $row && ($timeIn !== '-' || $timeOut !== '-');

                                $chipText = null;
                                $chipClass = '';

                                if ($isCurrentMonth) {
                                    $status = $row['status'] ?? null;
                                    if (
                                        $hasTime &&
                                        in_array($status, ['Terlambat', 'Sakit', 'Izin', 'Perjalanan Dinas'], true)
                                    ) {
                                        $chipText = $timeIn . ' - ' . $timeOut;
                                        if ($status === 'Perjalanan Dinas') {
                                            $chipClass = 'chip-dinas';
                                        } elseif ($status === 'Sakit') {
                                            $chipClass = 'chip-sakit';
                                        } elseif ($status === 'Izin') {
                                            $chipClass = 'chip-izin';
                                        } else {
                                            $chipClass = 'chip-terlambat';
                                        }
                                    } elseif ($status === 'Perjalanan Dinas') {
                                        $chipText = 'Perjalanan Dinas';
                                        $chipClass = 'chip-dinas';
                                    } elseif ($status === 'Cuti') {
                                        $chipText = null;
                                        $chipClass = '';
                                    } elseif ($status === 'Sakit') {
                                        $chipText = 'Sakit';
                                        $chipClass = 'chip-sakit';
                                    } elseif ($status === 'Izin') {
                                        $chipText = 'Izin';
                                        $chipClass = 'chip-izin';
                                    } elseif ($status === 'Tanpa Keterangan') {
                                        $chipText = 'Lupa Presensi';
                                        $chipClass = 'chip-lupa';
                                    } elseif ($status === 'Terlambat') {
                                        $chipText = 'Terlambat';
                                        $chipClass = 'chip-terlambat';
                                    } elseif ($hasTime) {
                                        $chipText = $timeIn . ' - ' . $timeOut;
                                        $chipClass = 'chip-hadir';
                                    } elseif ($isWeekend || $isNationalHoliday) {
                                        $chipText = 'Libur';
                                        $chipClass = 'chip-lupa';
                                    }
                                }
                            @endphp

                            <div
                                class="presence-cell {{ !$isCurrentMonth ? 'muted' : '' }} {{ $isWeekend || $isNationalHoliday ? 'holiday' : '' }}">
                                <div class="presence-date">{{ $day->format('d') }}</div>
                                @if ($chipText)
                                    <div class="presence-chip {{ $chipClass }}">{{ $chipText }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="presence-legend">
                    <span><i class="chip-hadir"></i> Hadir</span>
                    <span><i class="chip-dinas"></i> Perjalanan Dinas</span>
                    <span><i class="chip-cuti"></i> Cuti</span>
                    <span><i class="chip-sakit"></i> Sakit</span>
                    <span><i class="chip-izin"></i> Izin</span>
                    <span><i class="chip-terlambat"></i> Terlambat</span>
                    <span><i class="chip-lupa"></i> Lupa Presensi</span>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script-bottom')
    @if ($selectedUser)
        <script>
            (function() {
                var btn = document.getElementById('export-pdf-btn');
                if (!btn || typeof pdfMake === 'undefined') {
                    return;
                }

                var rows = @json($pdfRows ?? []);
                var userName = @json(optional($selectedUser)->name ?? '-');
                var campus = @json(optional($selectedUser)->institution ?? '-');
                var divisionName = @json(optional(optional($selectedUser)->division)->name ?? '-');
                var mentorName = @json(optional(optional($selectedUser)->mentor)->name ?? '-');
                var selectedMonth = @json($selectedMonth);

                function statusCodeFromStatus(status) {
                    var map = {
                        'Perjalanan Dinas': 'PD',
                        'Izin': 'I',
                        'Sakit': 'S',
                        'Cuti': 'C',
                        'Tanpa Keterangan': 'TK',
                        'Terlambat': 'T'
                    };
                    return map[status] || '';
                }

                function shortDayName(dayName) {
                    var map = {
                        'Minggu': 'Minggu',
                        'Senin': 'Senin',
                        'Selasa': 'Selasa',
                        'Rabu': 'Rabu',
                        'Kamis': 'Kamis',
                        'Jumat': 'Jumat',
                        'Sabtu': 'Sabtu'
                    };
                    return map[dayName] || dayName;
                }

                function buildLastDateLabel(month) {
                    var now = new Date();
                    var parts = String(month || '').split('-');
                    var y = parseInt(parts[0], 10);
                    var m = parseInt(parts[1], 10);
                    if (!y || !m || m < 1 || m > 12) {
                        y = now.getFullYear();
                        m = now.getMonth() + 1;
                    }
                    var lastDate = new Date(y, m, 0);
                    return lastDate.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
                }

                btn.addEventListener('click', function() {
                    var signDateLabel = buildLastDateLabel(selectedMonth);
                    var body = [];
                    body.push([{
                            text: 'Tgl',
                            style: 'th',
                            rowSpan: 2
                        },
                        {
                            text: 'Hari',
                            style: 'th',
                            rowSpan: 2
                        },
                        {
                            text: 'Presensi',
                            style: 'th',
                            colSpan: 2
                        },
                        {},
                        {
                            text: 'Ket.',
                            style: 'th',
                            rowSpan: 2
                        },
                        {
                            text: 'Keterangan',
                            style: 'th',
                            rowSpan: 2
                        }
                    ]);
                    body.push([{}, {},
                        {
                            text: 'Masuk',
                            style: 'thSub'
                        },
                        {
                            text: 'Pulang',
                            style: 'thSub'
                        },
                        {},
                        {}
                    ]);

                    var keteranganLines = [
                        'Kode Keterangan:',
                        'I : Izin',
                        'TL : Tugas Lain',
                        'H : Hadir / Kehadiran',
                        'T : Terlambat',
                        'S : Sakit',
                        'C : Cuti',
                        'LH : Libur Hari Besar / Nasional',
                        'CB : Cuti Bersama',
                        'LS : Libur Hari Sabtu / Minggu',
                        'TK : Tanpa Keterangan'
                    ];

                    var totalDataRows = rows.length;
                    var totalHariKerja = rows.filter(function(r) {
                        return !r.is_holiday;
                    }).length;
                    var totalHariMasukKerja = rows.filter(function(r) {
                        var hasIn = r.time_in && r.time_in !== '-';
                        var hasOut = r.time_out && r.time_out !== '-';
                        var code = (r.status_code || '').toUpperCase();
                        return !r.is_holiday && (hasIn || hasOut || code === 'H');
                    }).length;
                    var rightColRows = new Array(totalDataRows).fill(null).map(function() {
                        return {
                            text: '',
                            style: 'tdKeterangan'
                        };
                    });

                    // Anchor tanda tangan di area bawah tetap + sisakan zona kosong agar tidak bentrok
                    var signStart = Math.max(totalDataRows - 10, 0);
                    var codeAreaEnd = Math.max(signStart - 4, 0);

                    var lineCursor = 0;
                    for (var li = 0; li < keteranganLines.length && lineCursor < codeAreaEnd; li++) {
                        rightColRows[lineCursor] = {
                            text: keteranganLines[li],
                            style: 'tdKeterangan'
                        };
                        lineCursor++;
                    }

                    // Pastikan ada gap kosong sebelum blok tanda tangan
                    if (signStart - 3 >= 0) {
                        rightColRows[signStart - 3] = {
                            text: 'Total Hari Kerja: ' + totalHariKerja + ' hari',
                            style: 'tdKeterangan',
                            alignment: 'left'
                        };
                    }
                    if (signStart - 2 >= 0) {
                        rightColRows[signStart - 2] = {
                            text: 'Total Hari Masuk Kerja: ' + totalHariMasukKerja + ' hari',
                            style: 'tdKeterangan',
                            alignment: 'left'
                        };
                    }
                    if (signStart - 1 >= 0) {
                        rightColRows[signStart - 1] = {
                            text: '',
                            style: 'tdKeterangan'
                        };
                    }

                    if (signStart < totalDataRows) {
                        rightColRows[signStart] = {
                            text: signDateLabel,
                            style: 'tdDateSignRight',
                            margin: [0, 4, 0, 0]
                        };
                    }
                    if (signStart + 1 < totalDataRows) {
                        rightColRows[signStart + 1] = {
                            text: 'Mengetahui dan Menyetujui,',
                            style: 'tdDateSignRight'
                        };
                    }
                    if (signStart + 3 < totalDataRows) {
                        rightColRows[signStart + 3] = {
                            columns: [{
                                    width: '*',
                                    text: 'USER',
                                    alignment: 'center',
                                    style: 'tdSignRole'
                                },
                                {
                                    width: '*',
                                    text: 'MENTOR',
                                    alignment: 'center',
                                    style: 'tdSignRole'
                                }
                            ],
                            margin: [0, 3, 0, 0]
                        };
                    }
                    if (signStart + 6 < totalDataRows) {
                        rightColRows[signStart + 6] = {
                            columns: [{
                                    width: '*',
                                    text: '_______________________',
                                    alignment: 'center',
                                    style: 'tdSignName'
                                },
                                {
                                    width: '*',
                                    text: '_______________________',
                                    alignment: 'center',
                                    style: 'tdSignName'
                                }
                            ],
                            margin: [0, 2, 0, 0]
                        };
                    }
                    if (signStart + 7 < totalDataRows) {
                        rightColRows[signStart + 7] = {
                            columns: [{
                                    width: '*',
                                    text: String(userName || '-').toUpperCase(),
                                    alignment: 'center',
                                    style: 'tdSignName',
                                    noWrap: true
                                },
                                {
                                    width: '*',
                                    text: String(mentorName || '-').toUpperCase(),
                                    alignment: 'center',
                                    style: 'tdSignName',
                                    noWrap: true
                                }
                            ],
                            margin: [0, 1, 0, 0]
                        };
                    }

                    rows.forEach(function(row, index) {
                        var dayLabel = shortDayName(row.day_name || new Date(row.date).toLocaleDateString(
                            'id-ID', {
                                weekday: 'long'
                            }));
                        var dateNum = String(new Date(row.date).getDate()).padStart(2, '0');
                        var status = row.status || '';
                        var isHoliday = !!row.is_holiday;

                        var rightCell = rightColRows[index] || {
                            text: '',
                            style: 'tdKeterangan'
                        };
                        rightCell.border = [
                            true,
                            index === 0,
                            true,
                            index === (totalDataRows - 1)
                        ];

                        body.push([{
                                text: dateNum,
                                style: 'tdCenter',
                                fillColor: isHoliday ? '#8ff3ea' : null
                            },
                            {
                                text: dayLabel,
                                style: 'td',
                                fillColor: isHoliday ? '#8ff3ea' : null
                            },
                            {
                                text: row.time_in || '-',
                                style: 'tdCenter',
                                fillColor: isHoliday ? '#8ff3ea' : null
                            },
                            {
                                text: row.time_out || '-',
                                style: 'tdCenter',
                                fillColor: isHoliday ? '#8ff3ea' : null
                            },
                            {
                                text: row.status_code || statusCodeFromStatus(status),
                                style: 'tdCenter',
                                fillColor: isHoliday ? '#8ff3ea' : null
                            },
                            rightCell
                        ]);
                    });

                    var docDefinition = {
                        pageOrientation: 'portrait',
                        pageSize: 'A4',
                        pageMargins: [18, 14, 18, 14],
                        content: [{
                                text: 'PT. PRIMA LAYANAN NIAGA SUKU CADANG (PLNSC)',
                                bold: true,
                                fontSize: 9,
                                margin: [0, 0, 0, 1]
                            },
                            {
                                text: 'Nama : ' + String(userName || '-').toUpperCase(),
                                fontSize: 8
                            },
                            {
                                text: 'Asal Kampus : ' + String(campus || '-').toUpperCase(),
                                fontSize: 8
                            },
                            {
                                text: 'Divisi : ' + String(divisionName || '-').toUpperCase(),
                                fontSize: 8
                            },
                            {
                                text: 'Mentor : ' + String(mentorName || '-').toUpperCase(),
                                fontSize: 8
                            },
                            {
                                canvas: [{
                                    type: 'line',
                                    x1: 0,
                                    y1: 0,
                                    x2: 559,
                                    y2: 0,
                                    lineWidth: 0.6,
                                    lineColor: '#000000'
                                }],
                                margin: [0, 4, 0, 3]
                            },
                            {
                                text: 'DAFTAR KEHADIRAN PROGRAM MAGANG PT PLNSC',
                                bold: true,
                                alignment: 'center',
                                margin: [0, 5, 0, 0],
                                fontSize: 9
                            },
                            {
                                text: 'Bulan ' + selectedMonth,
                                bold: true,
                                alignment: 'center',
                                margin: [0, 0, 0, 5],
                                fontSize: 8
                            },
                            {
                                table: {
                                    headerRows: 2,
                                    widths: [32, 56, 82, 82, 30, '*'],
                                    heights: function(rowIndex) {
                                        if (rowIndex <= 1) return 12;
                                        return 12.6;
                                    },
                                    body: body
                                },
                                layout: {
                                    fillColor: function(rowIndex) {
                                        return rowIndex <= 1 ? '#f4f4f4' : null;
                                    },
                                    hLineWidth: function() {
                                        return 0.8;
                                    },
                                    vLineWidth: function() {
                                        return 0.8;
                                    },
                                    hLineColor: function() {
                                        return '#000000';
                                    },
                                    vLineColor: function() {
                                        return '#000000';
                                    },
                                    paddingLeft: function(i) {
                                        return i === 5 ? 3 : 2;
                                    },
                                    paddingRight: function(i) {
                                        return i === 4 ? 3 : 2;
                                    },
                                    paddingTop: function() {
                                        return 2;
                                    },
                                    paddingBottom: function() {
                                        return 2;
                                    }
                                },
                                margin: [0, 0, 0, 4]
                            },
                            {
                                text: 'Keterangan:\n1. Jika tidak hadir harap sertakan surat keterangan sakit / izin.\n2. Jika keterangan karena izin, mohon lampirkan surat keterangan dokter.',
                                fontSize: 7
                            },
                            {
                                text: 'Dokumen ini dicetak otomatis oleh Sistem Pengelolaan Magang PT PLNSC.',
                                fontSize: 6.2,
                                italics: true,
                                margin: [0, 3, 0, 0]
                            }
                        ],
                        styles: {
                            th: {
                                bold: true,
                                alignment: 'center',
                                valign: 'middle',
                                fontSize: 7
                            },
                            thSub: {
                                bold: true,
                                alignment: 'center',
                                valign: 'middle',
                                fontSize: 6.5
                            },
                            td: {
                                fontSize: 6.5,
                                valign: 'middle'
                            },
                            tdCenter: {
                                alignment: 'center',
                                valign: 'middle',
                                fontSize: 6.5
                            },
                            tdRight: {
                                alignment: 'right',
                                fontSize: 6.5
                            },
                            tdKeterangan: {
                                fontSize: 5.4,
                                lineHeight: 1.02,
                                valign: 'middle'
                            },
                            tdDateSignRight: {
                                fontSize: 6.3,
                                alignment: 'center'
                            },
                            tdSignRole: {
                                fontSize: 6.4,
                                bold: true
                            },
                            tdSignName: {
                                fontSize: 6.5,
                                bold: true
                            }
                        },
                        defaultStyle: {
                            fontSize: 6.5
                        }
                    };

                    var userPart = (userName || 'user').trim().replace(/[\\/:*?"<>|]+/g, '').replace(/\s+/g,
                        '-');
                    var monthYearPart = selectedMonth || '';
                    if (/^\d{4}-\d{2}$/.test(monthYearPart)) {
                        var parts = monthYearPart.split('-');
                        monthYearPart = parts[1] + '-' + parts[0];
                    }
                    var filename = 'Rekap Absensi-' + userPart + '-' + monthYearPart + '.pdf';
                    pdfMake.createPdf(docDefinition).download(filename);
                });
            })();
        </script>
    @endif
@endsection
