@extends('layouts.master')

@section('css')
    <style>
        :root {
            --pm-accent: #2f7fbc;
            --pm-ink: #1f2430;
            --pm-muted: #6b7280;
            --pm-soft: #f5f8fa;
            --pm-hover: #eff3f6;
            --pm-hair: #eff2f4;
        }

        /* Tombol Tambah (kanan atas): bulat penuh, warna sama dengan filter */
        .btn-primary.btn-flat {
            border-radius: 999px !important;
            padding: 7px 20px;
            background: var(--pm-accent);
            border-color: var(--pm-accent);
            box-shadow: 0 4px 12px rgba(47, 127, 188, .25);
        }

        .btn-primary.btn-flat:hover,
        .btn-primary.btn-flat:focus {
            background: #1b6aa8;
            border-color: #1b6aa8;
        }

        /* Kartu putih: sudut bulat + dua lapisan tipis di belakangnya (backdrop) */
        .card.admin-card {
            border: 0 !important;
            border-radius: 24px !important;
            background: #fff;
            margin-bottom: 36px;
            box-shadow:
                0 14px 0 -7px rgba(47, 127, 188, .10),
                0 26px 0 -14px rgba(47, 127, 188, .06),
                0 18px 40px rgba(31, 36, 48, .07);
        }

        .card.admin-card .card-body {
            padding: 30px 34px 26px;
            border-radius: 24px;
        }

        /* Baris atas: filter di kiri, alat di kanan */
        .pm-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }

        .pm-filter {
            display: inline-flex;
            gap: 4px;
            padding: 5px;
            background: var(--pm-soft);
            border-radius: 999px;
        }

        .pm-filter button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 0;
            background: transparent;
            padding: 7px 18px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 500;
            color: var(--pm-muted);
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .pm-filter button:hover {
            color: var(--pm-ink);
        }

        .pm-filter button:focus-visible {
            outline: 2px solid var(--pm-accent);
            outline-offset: 2px;
        }

        .pm-filter .n {
            min-width: 24px;
            height: 24px;
            padding: 0 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #e3e9ee;
            color: #444f56;
            font-size: 13px;
            font-weight: 600;
        }

        .pm-filter button.is-on {
            background: var(--pm-accent);
            color: #fff;
            font-weight: 600;
        }

        .pm-filter button.is-on .n {
            background: #fff;
            color: var(--pm-accent);
        }

        .pm-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1 1 340px;
            justify-content: flex-end;
        }

        .pm-search {
            position: relative;
            flex: 1 1 200px;
            max-width: 340px;
        }

        .pm-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--pm-muted);
            font-size: 15px;
            pointer-events: none;
        }

        .pm-search input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 40px;
            border: 1px solid transparent;
            border-radius: 12px;
            background: var(--pm-soft);
            color: var(--pm-ink);
            font-size: 15px;
            transition: background .15s, border-color .15s, box-shadow .15s;
        }

        .pm-search input::placeholder {
            color: #a3a8b5;
        }

        .pm-search input:focus {
            outline: 0;
            background: #fff;
            border-color: var(--pm-accent);
            box-shadow: 0 0 0 3px rgba(47, 127, 188, .14);
        }

        .pm-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            height: 44px;
            padding: 0 16px;
            background: var(--pm-soft);
            border: 1px solid transparent;
            border-radius: 12px;
            color: var(--pm-ink);
            font-size: 15px;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .pm-btn:hover {
            background: var(--pm-hover);
            color: var(--pm-accent);
        }

        .pm-btn:focus {
            outline: 0;
            box-shadow: 0 0 0 3px rgba(47, 127, 188, .14);
        }

        .pm-btn.icon {
            width: 44px;
            padding: 0;
            justify-content: center;
        }

        .pm-btn .fa-chevron-down {
            font-size: 11px;
        }

        .pm-menu {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(31, 36, 48, .14);
            padding: 6px;
            min-width: 190px;
        }

        .pm-menu .dropdown-item {
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 14.5px;
            color: var(--pm-ink);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pm-menu .dropdown-item:hover {
            background: var(--pm-soft);
        }

        .pm-menu .dropdown-item i {
            width: 16px;
            color: var(--pm-muted);
            text-align: center;
        }

        .pm-menu label.dropdown-item {
            cursor: pointer;
            margin: 0;
        }

        .pm-menu input[type=checkbox] {
            accent-color: var(--pm-accent);
            margin: 0;
        }

        /* Tabel: tanpa kotak, hanya pita header dan garis tipis antar baris */
        /* Garis kotak di luar tabel berasal dari tema (.table-responsive / wrapper DataTables) */
        .admin-card .table-responsive,
        .admin-card .dataTables_wrapper,
        .admin-card .dataTables_wrapper .row,
        .admin-card table.dataTable {
            border: 0 !important;
            box-shadow: none !important;
            outline: 0 !important;
        }

        #datatable-users,
        #datatable-users th,
        #datatable-users td {
            border-left: 0 !important;
            border-right: 0 !important;
        }

        #datatable-users {
            margin: 0;
            border-collapse: separate !important;
            border-spacing: 0;
            border: 0 !important;
        }

        #datatable-users thead th {
            background: var(--pm-soft);
            color: var(--pm-ink);
            font-size: 15px;
            font-weight: 600;
            padding: 14px 22px;
            border-top: 0 !important;
            border-bottom: 0 !important;
        }

        #datatable-users thead th:first-child {
            border-radius: 12px 0 0 12px;
        }

        #datatable-users thead th:last-child {
            border-radius: 0 12px 12px 0;
        }

        #datatable-users tbody td {
            padding: 18px 22px;
            vertical-align: middle;
            color: var(--pm-ink);
            font-size: 15px;
            background: transparent;
            border-top: 0 !important;
            border-bottom: 1px solid var(--pm-hair) !important;
        }

        #datatable-users.table-striped tbody tr:nth-of-type(odd) {
            background: transparent;
        }

        #datatable-users tbody tr:hover td {
            background: var(--pm-soft);
            border-bottom-color: transparent !important;
        }

        #datatable-users tbody tr:hover td:first-child {
            border-radius: 12px 0 0 12px;
        }

        #datatable-users tbody tr:hover td:last-child {
            border-radius: 0 12px 12px 0;
        }

        #datatable-users tbody tr:last-child td {
            border-bottom-color: transparent !important;
        }

        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting_desc:after {
            opacity: .3;
            right: .7em;
        }

        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:after {
            opacity: 1;
        }

        .pm-name {
            font-weight: 700;
        }

        .pm-empty {
            color: #b3b8c4;
        }

        /* Status */
        .pm-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 13px;
            border-radius: 8px;
            font-size: 14.5px;
            font-weight: 500;
        }

        .pm-status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .pm-status.active {
            background: #e3f6ea;
            color: #18803c;
        }

        .pm-status.inactive {
            background: #fde4e4;
            color: #b42318;
        }

        /* Aksi */
        .pm-actions {
            display: flex;
            gap: 4px;
            justify-content: flex-end;
        }

        .pm-act {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: #3a3f4d;
            font-size: 17px;
            transition: background .15s, color .15s;
        }

        .pm-act:hover {
            text-decoration: none;
            background: #e4eff7;
            color: var(--pm-accent);
        }

        .pm-act.danger:hover {
            background: #fde4e4;
            color: #b42318;
        }

        .pm-act:focus-visible {
            outline: 2px solid var(--pm-accent);
            outline-offset: 1px;
        }

        /* Footer: info di kiri, paginasi berupa tombol terpisah di kanan */
        .pm-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .pm-foot .dataTables_info {
            padding: 0 !important;
            color: var(--pm-muted);
            font-size: 14.5px;
        }

        .pm-foot .dataTables_paginate {
            margin: 0;
            padding: 0;
        }

        .pm-foot .pagination {
            margin: 0;
            gap: 6px;
        }

        .pm-foot .page-item .page-link {
            margin: 0;
            padding: 8px 15px;
            font-size: 14.5px;
            border: 0;
            border-radius: 10px !important;
            background: var(--pm-soft);
            color: #444f56;
        }

        .pm-foot .page-item .page-link:hover {
            background: var(--pm-hover);
            color: var(--pm-accent);
        }

        .pm-foot .page-item.active .page-link {
            background: var(--pm-accent);
            color: #fff;
        }

        .pm-foot .page-item.disabled .page-link {
            background: transparent;
            color: #b3b8c4;
        }

        @media (max-width: 767px) {
            .card.admin-card .card-body {
                padding: 18px 16px;
            }

            .pm-tools {
                flex-basis: 100%;
                justify-content: stretch;
            }

            .pm-search {
                max-width: none;
            }

            .pm-filter {
                display: flex;
                width: 100%;
            }

            .pm-filter button {
                flex: 1;
                justify-content: center;
                padding: 7px 6px;
                font-size: 14px;
            }

            #datatable-users thead th,
            #datatable-users tbody td {
                padding-left: 14px;
                padding-right: 14px;
            }
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">{{ __('global.users') }}</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('global.users') }}</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('global.users_list') }}</a></li>
        </ol>
    </div>
@endsection

@section('content')
    @include('includes.flash')
    <!--Show Validation Errors here-->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!--End showing Validation Errors here-->

    @php
        // Active = hari ini berada di antara tanggal masuk dan tanggal selesai magang
        $isActiveIntern = function ($e) {
            if (!$e->internship_start_date || !$e->internship_end_date) {
                return false;
            }
            $start = \Carbon\Carbon::parse($e->internship_start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($e->internship_end_date)->endOfDay();
            return \Carbon\Carbon::now()->between($start, $end);
        };
        $activeCount = $users->filter($isActiveIntern)->count();
        $totalCount = $users->count();
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-body">

                    {{-- Filter status + alat --}}
                    <div class="pm-toolbar">
                        <div class="pm-filter" id="pm-filter" role="group" aria-label="Filter status magang">
                            <button type="button" class="is-on" data-filter="">Semua<span
                                    class="n">{{ $totalCount }}</span></button>
                            <button type="button" data-filter="^\s*Active\s*$">Active<span
                                    class="n">{{ $activeCount }}</span></button>
                            <button type="button" data-filter="^\s*Inactive\s*$">Inactive<span
                                    class="n">{{ $totalCount - $activeCount }}</span></button>
                        </div>

                        <div class="pm-tools">
                            <div class="pm-search">
                                <i class="fa fa-search"></i>
                                <input type="search" id="pm-search" placeholder="Cari nama, jurusan, atau divisi"
                                    aria-label="Cari peserta magang" autocomplete="off">
                            </div>
                            <div class="dropdown">
                                <button type="button" class="pm-btn" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    Ekspor <i class="fa fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right pm-menu">
                                    <a class="dropdown-item" href="javascript:void(0);" data-export="0"><i
                                            class="fa fa-copy"></i> Salin</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-export="1"><i
                                            class="fa fa-file-excel"></i> Excel</a>
                                    @unless (auth()->user()->isMentor())
                                        <a class="dropdown-item" href="{{ route('users.export.detail') }}"><i
                                                class="fa fa-file-excel"></i> Excel Detail</a>
                                    @endunless
                                    <a class="dropdown-item" href="javascript:void(0);" data-export="2"><i
                                            class="fa fa-file-pdf"></i> PDF</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button type="button" class="pm-btn icon" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false" title="Tampilkan kolom" aria-label="Tampilkan kolom">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right pm-menu" id="pm-colvis"></div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable-users" class="table dt-responsive nowrap" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th data-priority="1">{{ __('global.name') }}</th>
                                    <th data-priority="3">{{ __('global.major') }}</th>
                                    <th data-priority="4">Periode Magang</th>
                                    <th data-priority="5">Divisi</th>
                                    <th data-priority="2">Status</th>
                                    <th data-priority="1" class="text-right">{{ __('global.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @php
                                        $periodMonths = null;
                                        if ($user->internship_start_date && $user->internship_end_date) {
                                            $s = \Carbon\Carbon::parse($user->internship_start_date)->startOfDay();
                                            $e = \Carbon\Carbon::parse($user->internship_end_date)->startOfDay();
                                            $periodMonths = max(
                                                ($e->year - $s->year) * 12 + ($e->month - $s->month) + 1,
                                                1,
                                            );
                                        }
                                        $divisionName = optional($user->division)->name;
                                        $isActive = $isActiveIntern($user);
                                    @endphp
                                    <tr>
                                        <td class="pm-name">{{ $user->name }}</td>
                                        <td>{!! $user->major ? e($user->major) : '<span class="pm-empty">-</span>' !!}</td>
                                        <td data-order="{{ $periodMonths ?? 0 }}">
                                            @if ($periodMonths)
                                                {{ $periodMonths }} bulan
                                            @else
                                                <span class="pm-empty">-</span>
                                            @endif
                                        </td>
                                        <td>{!! $divisionName ? e($divisionName) : '<span class="pm-empty">-</span>' !!}</td>
                                        <td data-order="{{ $isActive ? 0 : 1 }}"
                                            data-search="{{ $isActive ? 'Active' : 'Inactive' }}"><span
                                                class="pm-status {{ $isActive ? 'active' : 'inactive' }}">{{ $isActive ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td>
                                            <div class="pm-actions">
                                                <a href="{{ route('users.show', $user->name) }}" class="pm-act"
                                                    title="Detail" aria-label="Detail {{ $user->name }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->

@endsection


@section('script')
    <script>
        $(function() {
            // Kolom Aksi (indeks 5) tidak ikut diekspor
            var exportCols = {
                columns: [0, 1, 2, 3, 4]
            };

            var table = $('#datatable-users').DataTable({
                lengthChange: false,
                pageLength: 10,
                order: [
                    [0, 'asc']
                ],
                // Pencarian & tombol dibuat manual di atas tabel, jadi dom hanya tabel + info + paginasi
                dom: 'rt<"pm-foot"ip>',
                columnDefs: [{
                    orderable: false,
                    targets: 5
                }],
                language: {
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ peserta',
                    infoEmpty: 'Tidak ada peserta',
                    infoFiltered: '(disaring dari _MAX_ peserta)',
                    zeroRecords: 'Tidak ada peserta yang cocok',
                    paginate: {
                        previous: 'Sebelumnya',
                        next: 'Berikutnya'
                    }
                },
                buttons: [{
                        extend: 'copy',
                        exportOptions: exportCols
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        exportOptions: exportCols,
                        customize: function(xlsx) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            var styles = xlsx.xl['styles.xml'];

                            // Add a border style
                            var borders = $('borders', styles);
                            var borderCount = borders.attr('count');
                            borders.append(
                                '<border>' +
                                '<left style="thin"><color auto="1"/></left>' +
                                '<right style="thin"><color auto="1"/></right>' +
                                '<top style="thin"><color auto="1"/></top>' +
                                '<bottom style="thin"><color auto="1"/></bottom>' +
                                '<diagonal/>' +
                                '</border>'
                            );
                            borders.attr('count', parseInt(borderCount, 10) + 1);

                            // Add a cellXfs style that uses the new border
                            var cellXfs = $('cellXfs', styles);
                            var xfCount = cellXfs.attr('count');
                            cellXfs.append(
                                '<xf xfId="0" applyBorder="1" borderId="' + borderCount + '"/>'
                            );
                            cellXfs.attr('count', parseInt(xfCount, 10) + 1);
                            var borderStyleIndex = xfCount;

                            // Apply border style to all cells
                            $('row c', sheet).attr('s', borderStyleIndex);

                            // Column widths (5 kolom yang diekspor)
                            var cols = $('cols', sheet);
                            if (cols.length === 0) {
                                cols = $('<cols/>').prependTo(sheet);
                            } else {
                                cols.empty();
                            }

                            var widths = [30, 24, 22, 34, 14];
                            for (var i = 0; i < widths.length; i++) {
                                cols.append(
                                    '<col min="' + (i + 1) + '" max="' + (i + 1) + '" width="' +
                                    widths[i] + '" customWidth="1"/>'
                                );
                            }
                        }
                    },
                    {
                        extend: 'pdf',
                        exportOptions: exportCols
                    }
                ]
            });

            // Ekspor: 0 = salin, 1 = Excel, 2 = PDF
            $('[data-export]').on('click', function() {
                table.button(parseInt($(this).data('export'), 10)).trigger();
            });

            // Tampilan kolom (Aksi selalu tampil)
            var $colvis = $('#pm-colvis');
            for (var i = 0; i < 5; i++) {
                var label = $.trim($(table.column(i).header()).text());
                $colvis.append(
                    '<label class="dropdown-item"><input type="checkbox" data-col="' + i + '" checked> ' +
                    label + '</label>'
                );
            }
            $colvis.on('click', function(e) {
                e.stopPropagation();
            });
            $colvis.on('change', 'input', function() {
                table.column($(this).data('col')).visible(this.checked);
            });

            // Pencarian
            $('#pm-search').on('input', function() {
                table.search(this.value).draw();
            });

            // Filter Active / Inactive (kolom Status = indeks 4)
            $('#pm-filter').on('click', 'button', function() {
                var $btn = $(this);
                $btn.addClass('is-on').siblings().removeClass('is-on');
                table.column(4).search($btn.data('filter'), true, false).draw();
            });
        });
    </script>
@endsection
