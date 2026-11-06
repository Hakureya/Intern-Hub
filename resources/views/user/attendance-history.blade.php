@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
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
        $rowsByDate = collect($rows)->keyBy('date');
    @endphp

    @include('layouts.user-navbar', ['active' => 'history'])

    <div class="history-page">
        <div class="history-shell">
            <div class="history-layout {{ $user ? 'has-detail' : '' }}">
            <div class="history-card">
                <div class="history-title">Riwayat Kehadiran</div>
                <form method="GET" action="{{ route('user.attendance.history') }}" class="history-filter">
                    <input type="month" class="form-control" name="month" value="{{ $selectedMonth }}">
                    <button class="btn btn-primary btn-sm" type="submit">Tampilkan</button>
                    @if ($user)
                        <button type="button" class="btn btn-primary btn-sm" id="export-pdf-btn">Ekspor PDF</button>
                    @endif
                </form>

                @if (!$user)
                    <div class="alert alert-info mb-0">Data user belum terhubung ke data peserta magang.</div>
                @else
                    <div class="mb-2 text-muted">
                        <strong>{{ $user->name }}</strong> - {{ $monthLabel }}
                    </div>

                    <div class="presence-calendar-scroll">
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
                                        $row = $rowsByDate->get($dateKey);
                                        $timeIn = $row['time_in'] ?? '-';
                                        $timeOut = $row['time_out'] ?? '-';
                                        $hasTime = $row && ($timeIn !== '-' || $timeOut !== '-');

                                        $chipText = null;
                                        $chipClass = '';

                                        if ($isCurrentMonth) {
                                            $status = $row['status'] ?? null;
                                            if (
                                                $hasTime &&
                                                in_array($status, ['Terlambat', 'Perjalanan Dinas'], true)
                                            ) {
                                                $chipText = $timeIn . ' - ' . $timeOut;
                                                $chipClass = $status === 'Perjalanan Dinas' ? 'chip-dinas' : 'chip-terlambat';
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
                                    <div class="presence-cell {{ !$isCurrentMonth ? 'muted' : 'is-clickable' }} {{ $isWeekend || $isNationalHoliday ? 'holiday' : '' }} {{ $dateKey === now()->toDateString() ? 'is-today' : '' }}"
                                        @if ($isCurrentMonth) data-date="{{ $dateKey }}" role="button" tabindex="0"
                                            aria-label="Lihat detail {{ $day->translatedFormat('d F Y') }}" @endif>
                                        <div class="presence-date">{{ $day->format('d') }}</div>
                                        @if ($chipText)
                                            <div class="presence-chip {{ $chipClass }}">{{ $chipText }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="presence-legend">
                        <span><i class="chip-hadir"></i> Hadir</span>
                        <span><i class="chip-dinas"></i> Perjalanan Dinas</span>
                        <span><i class="chip-sakit"></i> Sakit</span>
                        <span><i class="chip-izin"></i> Izin</span>
                        <span><i class="chip-terlambat"></i> Terlambat</span>
                        <span><i class="chip-lupa"></i> Lupa Presensi</span>
                    </div>
                    <div class="history-hint">Pilih tanggal untuk melihat detail kehadiran pada hari itu.</div>
                @endif
            </div>

            @if ($user)
                <aside class="history-card history-detail" id="day-detail" aria-live="polite">
                    <div class="detail-eyebrow">Detail tanggal</div>
                    <h2 class="detail-date" id="detail-date">&nbsp;</h2>
                    <div class="detail-pills" id="detail-pills"></div>
                    <div class="detail-body" id="detail-body">
                        <p class="detail-empty">Pilih tanggal pada kalender untuk melihat detail.</p>
                    </div>
                </aside>
            @endif
            </div>
        </div>
    </div>
@endsection

@section('script-bottom')
    @if ($user)
        <script>
            (function() {
                var details = @json($dayDetails ?? []);
                var initialDate = @json($initialDate ?? null);
                var cells = document.querySelectorAll('.presence-cell[data-date]');
                var elDate = document.getElementById('detail-date');
                var elPills = document.getElementById('detail-pills');
                var elBody = document.getElementById('detail-body');
                var panel = document.getElementById('day-detail');
                if (!elDate || !elBody) return;

                var PILL = {
                    'Hadir': 'chip-hadir',
                    'Terlambat': 'chip-terlambat',
                    'Sakit': 'chip-sakit',
                    'Izin': 'chip-izin',
                    'Perjalanan Dinas': 'chip-dinas',
                    'Tanpa Keterangan': 'chip-lupa',
                    'Libur': 'chip-lupa',
                    'Cuti': 'chip-lupa',
                    'Belum Presensi': 'chip-lupa'
                };
                var STATUS_PILL = {
                    approved: 'detail-pill is-good',
                    rejected: 'detail-pill is-bad',
                    pending: 'detail-pill is-warn'
                };

                // Semua teks disisipkan lewat textContent (aman dari XSS untuk isian alasan dll.).
                function el(tag, cls, text) {
                    var n = document.createElement(tag);
                    if (cls) n.className = cls;
                    if (text !== undefined && text !== null) n.textContent = text;
                    return n;
                }

                function row(parent, label, value) {
                    var r = el('div', 'detail-row');
                    r.appendChild(el('span', 'detail-label', label));
                    r.appendChild(el('strong', 'detail-value', value || '\u2014'));
                    parent.appendChild(r);
                }

                function times(inT, outT) {
                    return 'Masuk ' + (inT || '\u2014') + '  \u00b7  Pulang ' + (outT || '\u2014');
                }

                function renderRequest(req) {
                    var box = el('div', 'detail-section');
                    var head = el('div', 'detail-section-head');
                    head.appendChild(el('span', 'detail-section-title', req.type_label));
                    head.appendChild(el('span', STATUS_PILL[req.status] || 'detail-pill', req.status_label));
                    box.appendChild(head);

                    if (req.type === 'lupa_absensi') {
                        row(box, 'Diajukan pada', req.submitted_at);
                        row(box, 'Sebelum', times(req.before_in, req.before_out));
                        row(box, 'Setelah', times(req.after_in, req.after_out));
                        row(box, 'Alasan', req.reason);
                    } else {
                        row(box, 'Periode', req.is_range ? req.period + ' (' + req.days + ' hari)' : req.period);
                        row(box, 'Diajukan pada', req.submitted_at);
                        row(box, 'Alasan', req.reason);
                    }
                    if (req.admin_note) row(box, 'Catatan admin', req.admin_note);
                    return box;
                }

                function render(date) {
                    var d = details[date];
                    elBody.innerHTML = '';
                    elPills.innerHTML = '';
                    if (!d) {
                        elDate.textContent = '\u00a0';
                        elBody.appendChild(el('p', 'detail-empty', 'Pilih tanggal pada kalender untuk melihat detail.'));
                        return;
                    }

                    elDate.textContent = d.label;
                    var pill = el('span', 'detail-pill ' + (PILL[d.status] || 'chip-lupa'), d.status_label);
                    elPills.appendChild(pill);
                    var hasCorrection = d.requests.some(function(r) {
                        return r.type === 'lupa_absensi';
                    });
                    if (hasCorrection) elPills.appendChild(el('span', 'detail-pill chip-lupa', 'Koreksi kehadiran'));
                    if (d.holiday_label && d.status !== 'Libur') elPills.appendChild(el('span', 'detail-pill chip-lupa', d.holiday_label));

                    // Presensi
                    var att = el('div', 'detail-section is-plain');
                    row(att, 'Jam masuk', d.time_in);
                    row(att, 'Jam pulang', d.time_out);
                    d.late_info.forEach(function(t) {
                        att.appendChild(el('p', 'detail-note is-warn', t));
                    });
                    if (d.note_in) row(att, 'Catatan masuk', d.note_in);
                    if (d.note_out) row(att, 'Catatan pulang', d.note_out);
                    elBody.appendChild(att);

                    if (!d.time_in && !d.time_out && !d.requests.length) {
                        elBody.appendChild(el('p', 'detail-empty', d.holiday_label ?
                            d.holiday_label + ' \u2014 tidak ada kewajiban presensi.' :
                            'Belum ada data kehadiran pada tanggal ini.'));
                    }

                    // Pengajuan (koreksi kehadiran, sakit, izin)
                    d.requests.forEach(function(req) {
                        elBody.appendChild(renderRequest(req));
                    });
                }

                function select(date, userInitiated) {
                    cells.forEach(function(c) {
                        c.classList.toggle('is-selected', c.getAttribute('data-date') === date);
                    });
                    render(date);
                    // Di layar kecil panel berada di bawah kalender: gulir agar terlihat.
                    if (userInitiated && panel && window.innerWidth < 992) {
                        panel.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                }

                cells.forEach(function(c) {
                    c.addEventListener('click', function() {
                        select(c.getAttribute('data-date'), true);
                    });
                    c.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            select(c.getAttribute('data-date'), true);
                        }
                    });
                });

                if (initialDate) select(initialDate, false);
            })();
        </script>
        <script>
            (function() {
                var btn = document.getElementById('export-pdf-btn');
                if (!btn || typeof pdfMake === 'undefined') return;

                var rows = @json($pdfRows ?? []);
                var userName = @json(optional($user)->name ?? '-');
                var campus = @json(optional($user)->institution ?? '-');
                var divisionName = @json(optional(optional($user)->division)->name ?? '-');
                var mentorName = @json(optional(optional($user)->mentor)->name ?? '-');
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
                        {}, {}
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
