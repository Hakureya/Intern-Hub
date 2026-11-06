@extends('layouts.master')

@section('css')
    <style>
        .magang-card {
            border: 1px solid #dfe6ef;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
            background: #fff;
        }

        .magang-stat-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #7b8794;
        }

        .magang-stat-value {
            font-size: 30px;
            line-height: 1;
            font-weight: 800;
            color: #1f2d3d;
        }

        .magang-section-title {
            font-size: 26px;
            font-weight: 800;
            color: #1f2d3d;
        }

        .magang-table th {
            font-size: 12px;
            text-transform: uppercase;
            color: #607085;
            background: #f5f8fc;
        }

        .magang-pill {
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .magang-chart-wrap {
            height: 320px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .magang-chart-canvas {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
        }

        .magang-chart-canvas canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .magang-filter .form-control,
        .magang-filter .btn {
            height: 38px;
        }

        @media (max-width: 767.98px) {
            .magang-section-title {
                font-size: 22px;
            }

            .magang-stat-title {
                font-size: 11px;
            }

            .magang-stat-value {
                font-size: 26px;
            }

            .magang-chart-wrap {
                height: 250px;
            }
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Pendaftaran Magang</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Beranda</a></li>
            <li class="breadcrumb-item active">Pendaftaran Magang</li>
        </ol>
    </div>
@endsection

@section('button')
    <a href="{{ route('admin.magang.export', request()->all()) }}" class="btn btn-success btn-sm">
        <i class="mdi mdi-download mr-1"></i> Export Excel
    </a>
@endsection

@section('content')
    @php
        $dataChartJenis = [
            'fg' => $total_fg ?? 0,
            'mhs' => $total_mhs ?? 0,
        ];

        $dataChartStatus = [
            'menunggu' => $total_menunggu ?? 0,
            'wawancara' => $total_wawancara ?? 0,
            'diterima' => $total_diterima ?? 0,
            'ditolak' => $total_ditolak ?? 0,
        ];
        $hasPendaftar = ($total_pelamar ?? 0) > 0;
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="magang-section-title">Manajemen Pendaftar</div>
            <div class="text-muted">Pantau data magang mahasiswa secara real-time.</div>
        </div>
    </div>

    <div class="row">
        <div class="col-6 col-md-6 col-xl-4 mb-2">
            <div class="magang-card p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="magang-stat-title">Total Pelamar</div>
                    <div class="magang-stat-value">{{ $total_pelamar }}</div>
                </div>
                <i class="mdi mdi-account-group-outline text-primary" style="font-size:30px"></i>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-4 mb-2">
            <div class="magang-card p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="magang-stat-title">Fresh Graduate</div>
                    <div class="magang-stat-value text-info">{{ $total_fg }}</div>
                </div>
                <i class="mdi mdi-school-outline text-info" style="font-size:30px"></i>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-4 mb-2">
            <div class="magang-card p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="magang-stat-title">Mahasiswa Aktif</div>
                    <div class="magang-stat-value text-primary">{{ $total_mhs }}</div>
                </div>
                <i class="mdi mdi-office-building-outline text-primary" style="font-size:30px"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-6 col-md-6 col-xl-4 mb-2">
            <div class="magang-card p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="magang-stat-title">Perlu Review</div>
                    <div class="magang-stat-value text-warning">{{ $total_menunggu }}</div>
                </div>
                <i class="mdi mdi-clock-outline text-warning" style="font-size:30px"></i>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-4 mb-2">
            <div class="magang-card p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="magang-stat-title">Tahap Wawancara</div>
                    <div class="magang-stat-value" style="color:#1b6aa8">{{ $total_wawancara }}</div>
                </div>
                <i class="mdi mdi-microphone-outline" style="font-size:30px;color:#1b6aa8"></i>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-4 mb-2">
            <div class="magang-card p-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="magang-stat-title">Lolos Seleksi</div>
                    <div class="magang-stat-value text-success">{{ $total_diterima }}</div>
                </div>
                <i class="mdi mdi-check-circle-outline text-success" style="font-size:30px"></i>
            </div>
        </div>
    </div>

    @if ($hasPendaftar)
        <div class="row">
            <div class="col-lg-6 mb-2">
                <div class="magang-card p-3 magang-chart-wrap">
                    <h5 class="mb-2">Komposisi Pelamar</h5>
                    <div class="magang-chart-canvas">
                        <canvas id="chartJenis"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-2">
                <div class="magang-card p-3 magang-chart-wrap">
                    <h5 class="mb-2">Funnel Seleksi</h5>
                    <div class="magang-chart-canvas">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12 mb-2">
                <div class="magang-card p-3 text-muted">
                    Belum ada data pelamar. Grafik akan tampil otomatis setelah ada pendaftar.
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="magang-card">
                <div class="p-3 border-bottom">
                    <form action="{{ route('admin.magang.dashboard') }}" method="GET"
                        class="form-row align-items-center magang-filter">
                        <div class="col-12 col-sm-6 col-md-2 mb-2">
                            <select name="filter_jenis" class="form-control" onchange="this.form.submit()">
                                <option value="">Semua Tipe</option>
                                <option value="fresh_graduate"
                                    {{ request('filter_jenis') == 'fresh_graduate' ? 'selected' : '' }}>Fresh Graduate
                                </option>
                                <option value="mahasiswa" {{ request('filter_jenis') == 'mahasiswa' ? 'selected' : '' }}>
                                    Mahasiswa</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2 mb-2">
                            <select name="filter_status" class="form-control" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="Menunggu" {{ request('filter_status') == 'Menunggu' ? 'selected' : '' }}>
                                    Menunggu</option>
                                <option value="Wawancara" {{ request('filter_status') == 'Wawancara' ? 'selected' : '' }}>
                                    Wawancara</option>
                                <option value="Diterima" {{ request('filter_status') == 'Diterima' ? 'selected' : '' }}>
                                    Diterima</option>
                                <option value="Ditolak" {{ request('filter_status') == 'Ditolak' ? 'selected' : '' }}>
                                    Ditolak</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 mb-2">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Cari nama / email / kampus">
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <button class="btn btn-primary btn-block" type="submit">Tampilkan</button>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <a href="{{ route('admin.magang.dashboard') }}" class="btn btn-light btn-block">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-hover mb-0 magang-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Asal Kampus</th>
                                <th>Jurusan</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendaftars as $p)
                                <tr>
                                    <td>{{ $p->nama }}</td>
                                    <td>{{ $p->email }}</td>
                                    <td>{{ $p->asal_kampus }}</td>
                                    <td>{{ $p->jurusan }}</td>
                                    <td>{{ $p->periode ?: '-' }}</td>
                                    <td>
                                        @if ($p->status === 'Diterima')
                                            <span class="magang-pill"
                                                style="background:#dcfce7;color:#166534">Diterima</span>
                                        @elseif($p->status === 'Ditolak')
                                            <span class="magang-pill"
                                                style="background:#fee2e2;color:#991b1b">Ditolak</span>
                                        @elseif($p->status === 'Wawancara')
                                            <span class="magang-pill"
                                                style="background:#d2e6f4;color:#134f82">Wawancara</span>
                                        @else
                                            <span class="magang-pill"
                                                style="background:#fef3c7;color:#92400e">Menunggu</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.magang.detail', $p->id) }}"
                                            class="btn btn-info btn-sm">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada data pelamar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none p-2">
                    @forelse($pendaftars as $p)
                        <div class="magang-card p-3 mb-2">
                            <div class="font-weight-bold">{{ $p->nama }}</div>
                            <div class="text-muted small text-break">{{ $p->email }}</div>
                            <div class="small mt-1">{{ $p->asal_kampus }} - {{ $p->jurusan }}</div>
                            <div class="small mt-1">Periode: {{ $p->periode ?: '-' }}</div>
                            <div class="mt-2">
                                @if ($p->status === 'Diterima')
                                    <span class="magang-pill" style="background:#dcfce7;color:#166534">Diterima</span>
                                @elseif($p->status === 'Ditolak')
                                    <span class="magang-pill" style="background:#fee2e2;color:#991b1b">Ditolak</span>
                                @elseif($p->status === 'Wawancara')
                                    <span class="magang-pill" style="background:#d2e6f4;color:#134f82">Wawancara</span>
                                @else
                                    <span class="magang-pill" style="background:#fef3c7;color:#92400e">Menunggu</span>
                                @endif
                            </div>
                            <a href="{{ route('admin.magang.detail', $p->id) }}"
                                class="btn btn-info btn-sm btn-block mt-2">Detail</a>
                        </div>
                    @empty
                        <div class="text-center text-muted p-3">Belum ada data pelamar.</div>
                    @endforelse
                </div>

                <div class="p-3">
                    {{ $pendaftars->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script-bottom')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            var dataJenis = @json($dataChartJenis);
            var dataStatus = @json($dataChartStatus);

            var jenisEl = document.getElementById('chartJenis');
            if (jenisEl) {
                new Chart(jenisEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Fresh Graduate', 'Mahasiswa Aktif'],
                        datasets: [{
                            data: [dataJenis.fg || 0, dataJenis.mhs || 0],
                            backgroundColor: ['#1b6aa8', '#7fb8dc']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            var statusEl = document.getElementById('chartStatus');
            if (statusEl) {
                new Chart(statusEl, {
                    type: 'bar',
                    data: {
                        labels: ['Menunggu', 'Wawancara', 'Diterima', 'Ditolak'],
                        datasets: [{
                            data: [
                                dataStatus.menunggu || 0,
                                dataStatus.wawancara || 0,
                                dataStatus.diterima || 0,
                                dataStatus.ditolak || 0
                            ],
                            backgroundColor: ['#dcb64f', '#1b6aa8', '#8aa83a', '#c0566f']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        resizeDelay: 120,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    callback: function(value) {
                                        return Number.isInteger(value) ? value : '';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endsection
