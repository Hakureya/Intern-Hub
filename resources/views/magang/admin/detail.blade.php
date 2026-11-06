@extends('layouts.master')

@section('css')
    <style>
        .magang-card {
            border: 1px solid #dfe6ef;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
            background: #fff;
        }

        .magang-doc-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 24px;
        }

        .magang-doc-block {
            min-width: 0;
        }

        .magang-doc-label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .magang-doc-row {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .magang-doc-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 34px;
            padding: 0 16px;
            border-radius: 999px;
            background: #e7f7ee;
            color: #0f766e;
            font-weight: 700;
            flex: 0 0 auto;
            text-decoration: none !important;
            border: 0;
            cursor: pointer;
        }

        .magang-doc-pill:hover {
            background: #d5f0e3;
            color: #0f766e;
        }

        .magang-doc-pill.disabled {
            opacity: .6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .magang-doc-file {
            min-width: 0;
            word-break: break-word;
            color: #64748b;
        }

        .magang-doc-divider {
            margin: 14px 0 12px;
            border-top: 1px solid #e2e8f0;
        }

        .magang-doc-section-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .magang-doc-support-list {
            display: grid;
            gap: 10px;
        }

        @media (max-width: 767.98px) {
            .magang-card {
                border-radius: 12px;
            }

            .magang-doc-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .magang-doc-row {
                flex-wrap: wrap;
                gap: 8px;
            }
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Detail Pelamar</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.magang.dashboard') }}">Pendaftaran Magang</a></li>
            <li class="breadcrumb-item active">{{ $pendaftar->nama }}</li>
        </ol>
    </div>
@endsection

@section('button')
    <a href="{{ route('admin.magang.dashboard') }}" class="btn btn-secondary btn-sm">Kembali</a>
@endsection

@section('content')
    @php
        $prettyFileName = function ($originalName, $path) {
            if (!empty($originalName)) {
                return $originalName;
            }

            if (empty($path)) {
                return 'Tidak ada file yang dipilih';
            }

            return basename($path);
        };
    @endphp
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="magang-card p-3">
                <h5 class="mb-3">Data Pelamar</h5>
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>Nama:</strong> {{ $pendaftar->nama }}</div>
                    <div class="col-md-6 mb-2"><strong>Email:</strong> {{ $pendaftar->email }}</div>
                    <div class="col-md-6 mb-2"><strong>No HP:</strong> {{ $pendaftar->no_hp }}</div>
                    <div class="col-md-6 mb-2"><strong>Jenis:</strong> {{ $pendaftar->jenis_magang }}</div>
                    <div class="col-md-6 mb-2"><strong>Kampus:</strong> {{ $pendaftar->asal_kampus }}</div>
                    <div class="col-md-6 mb-2"><strong>Jurusan:</strong> {{ $pendaftar->jurusan }}</div>
                    <div class="col-md-6 mb-2"><strong>IPK:</strong> {{ $pendaftar->ipk ?: '-' }}</div>
                    <div class="col-md-6 mb-2"><strong>Semester:</strong> {{ $pendaftar->semester ?: '-' }}</div>
                    <div class="col-md-12 mb-2"><strong>Periode:</strong> {{ $pendaftar->periode ?: '-' }}</div>
                    <div class="col-md-12 mb-2"><strong>Alamat:</strong> {{ $pendaftar->alamat ?: '-' }}</div>
                </div>
            </div>

            <div class="magang-card p-3 mt-3">
                <h5 class="mb-3">Dokumen</h5>
                <div class="magang-doc-grid">
                    <div class="magang-doc-block">
                        <span class="magang-doc-label">Surat Permohonan Magang</span>
                        <div class="magang-doc-row">
                            @if ($pendaftar->surat_permohonan_path)
                                <a class="magang-doc-pill" target="_blank"
                                    href="{{ asset('storage/' . $pendaftar->surat_permohonan_path) }}">Lihat File</a>
                            @else
                                <span class="magang-doc-pill disabled">Lihat File</span>
                            @endif
                            <span
                                class="magang-doc-file">{{ $prettyFileName($pendaftar->surat_permohonan_name ?? null, $pendaftar->surat_permohonan_path) }}</span>
                        </div>
                    </div>
                    <div class="magang-doc-block">
                        <span class="magang-doc-label">Curriculum Vitae (CV)</span>
                        <div class="magang-doc-row">
                            @if ($pendaftar->cv_path)
                                <a class="magang-doc-pill" target="_blank"
                                    href="{{ asset('storage/' . $pendaftar->cv_path) }}">Lihat File</a>
                            @else
                                <span class="magang-doc-pill disabled">Lihat File</span>
                            @endif
                            <span
                                class="magang-doc-file">{{ $prettyFileName($pendaftar->cv_name ?? null, $pendaftar->cv_path) }}</span>
                        </div>
                    </div>
                    <div class="magang-doc-block">
                        <span class="magang-doc-label">Transkrip Nilai Terakhir</span>
                        <div class="magang-doc-row">
                            @if ($pendaftar->transkrip_path)
                                <a class="magang-doc-pill" target="_blank"
                                    href="{{ asset('storage/' . $pendaftar->transkrip_path) }}">Lihat File</a>
                            @else
                                <span class="magang-doc-pill disabled">Lihat File</span>
                            @endif
                            <span
                                class="magang-doc-file">{{ $prettyFileName($pendaftar->transkrip_name ?? null, $pendaftar->transkrip_path) }}</span>
                        </div>
                    </div>
                    <div class="magang-doc-block">
                        <span class="magang-doc-label">Surat Pengantar</span>
                        <div class="magang-doc-row">
                            @if ($pendaftar->surat_path)
                                <a class="magang-doc-pill" target="_blank"
                                    href="{{ asset('storage/' . $pendaftar->surat_path) }}">Lihat File</a>
                            @else
                                <span class="magang-doc-pill disabled">Lihat File</span>
                            @endif
                            <span
                                class="magang-doc-file">{{ $prettyFileName($pendaftar->surat_name ?? null, $pendaftar->surat_path) }}</span>
                        </div>
                    </div>
                </div>

                <div class="magang-doc-divider"></div>
                <div class="magang-doc-section-title">Dokumen Pendukung (Opsional)</div>
                <div class="magang-doc-support-list">
                    @if (is_array($pendaftar->dokumen_pendukung_path) && count($pendaftar->dokumen_pendukung_path))
                        @php
                            $supportNames = is_array($pendaftar->dokumen_pendukung_name ?? null)
                                ? $pendaftar->dokumen_pendukung_name
                                : [];
                        @endphp
                        @foreach ($pendaftar->dokumen_pendukung_path as $path)
                            <div class="magang-doc-row">
                                @if ($path)
                                    <a class="magang-doc-pill" target="_blank" href="{{ asset('storage/' . $path) }}">Lihat
                                        File</a>
                                @else
                                    <span class="magang-doc-pill disabled">Lihat File</span>
                                @endif
                                <span
                                    class="magang-doc-file">{{ $prettyFileName($supportNames[$loop->index] ?? null, $path) }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="magang-doc-row">
                            <span class="magang-doc-pill">Lihat File</span>
                            <span class="magang-doc-file">Tidak ada file yang dipilih</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="magang-card p-3 mb-3">
                <h5 class="mb-3">Status Saat Ini</h5>
                <p><strong>{{ $pendaftar->status }}</strong></p>
                @if ($pendaftar->pesan)
                    <div class="alert alert-light border mb-0">{{ $pendaftar->pesan }}</div>
                @endif
            </div>

            <div class="magang-card p-3">
                <h5 class="mb-3">Ubah Status</h5>

                <form action="{{ route('admin.magang.update', $pendaftar->id) }}" method="POST" class="mb-3">
                    @csrf
                    <input type="hidden" name="status" value="Wawancara">
                    <div class="form-group">
                        <label>Waktu Wawancara</label>
                        <input type="datetime-local" name="tgl_wawancara" class="form-control"
                            value="{{ $pendaftar->wawancara_waktu ? $pendaftar->wawancara_waktu->format('Y-m-d\TH:i') : '' }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Lokasi / Link</label>
                        <input type="text" name="lokasi" class="form-control"
                            value="{{ $pendaftar->wawancara_lokasi }}" required>
                    </div>
                    <div class="form-group">
                        <label>Pesan</label>
                        <textarea name="pesan" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Set Wawancara</button>
                </form>

                <form action="{{ route('admin.magang.update', $pendaftar->id) }}" method="POST" class="mb-2">
                    @csrf
                    <input type="hidden" name="status" value="Diterima">
                    <div class="form-group">
                        <label>Pesan diterima (opsional)</label>
                        <textarea name="pesan" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-success btn-block" type="submit">Terima</button>
                </form>

                <form action="{{ route('admin.magang.update', $pendaftar->id) }}" method="POST" class="mb-2">
                    @csrf
                    <input type="hidden" name="status" value="Ditolak">
                    <div class="form-group">
                        <label>Pesan ditolak (opsional)</label>
                        <textarea name="pesan" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-danger btn-block" type="submit">Tolak</button>
                </form>

                <form action="{{ route('admin.magang.update', $pendaftar->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="Menunggu">
                    <button class="btn btn-light btn-block" type="submit">Reset ke Menunggu</button>
                </form>
            </div>
        </div>
    </div>
@endsection
