@extends('layouts.master')

@section('css')
    <!-- Table css -->
    <link href="{{ URL::asset('plugins/RWD-Table-Patterns/dist/css/rwd-table.min.css') }}" rel="stylesheet" type="text/css"
        media="screen">
    <style>
        .admin-card {
            border-radius: 16px;
            border: 1px solid #d9e7ef;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
        }

        .admin-card .card-body {
            padding: 20px;
        }

        .admin-table thead th {
            background: #f2f8fb;
            color: #5b6973;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #d9e7ef;
        }

        .admin-table tbody td {
            vertical-align: middle;
        }

        .file-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            background: #edf5fa;
            color: #134f82;
            text-decoration: none;
        }

        .export-box {
            border: 1px dashed #d9e7ef;
            border-radius: 12px;
            padding: 12px 16px;
            background: #fbfdfe;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">{{ __('global.final_report') }}</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item"><a href="">{{ __('global.final_report') }}</a></li>
        </ol>
    </div>
@endsection

@section('content')
    @include('includes.flash')
    @php
        if (!function_exists('format_filesize')) {
            function format_filesize($bytes)
            {
                $bytes = (int) $bytes;
                if ($bytes <= 0) {
                    return '-';
                }
                $units = ['B', 'KB', 'MB', 'GB'];
                $i = 0;
                while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
                }
                return number_format($bytes, 2) . ' ' . $units[$i];
            }
        }
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-body">
                    <div class="export-box mb-3 d-flex align-items-center justify-content-between flex-wrap">
                        <div class="mb-2 mb-md-0">
                            <strong>{{ __('global.export_zip') }}</strong>
                            <span class="text-muted">per bulan</span>
                        </div>
                        <form id="final-report-export-form" class="form-inline">
                            <input type="month" class="form-control mr-2" name="month" required>
                            <button class="btn btn-sm btn-primary" type="submit">{{ __('global.export_zip') }}</button>
                        </form>
                    </div>
                    <div class="table-rep-plugin">
                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                            <table id="datatable"
                                class="table table-striped table-bordered dt-responsive nowrap admin-table"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th data-priority="1">{{ __('global.date') }}</th>
                                        <th data-priority="2">ID</th>
                                        <th data-priority="3">{{ __('global.name') }}</th>
                                        <th data-priority="4">{{ __('global.final_report_document') }}</th>
                                        <th data-priority="5">{{ __('global.uploaded_by') }}</th>
                                        <th data-priority="6">Insight Peserta</th>
                                        <th data-priority="7">{{ __('global.file_size') }}</th>
                                        <th data-priority="8">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reports as $report)
                                        <tr>
                                            <td>{{ optional($report->created_at)->format('d-m-Y H:i') }}</td>
                                            <td>{{ optional($report->user)->id ?? '-' }}</td>
                                            <td>{{ optional($report->user)->name ?? '-' }}</td>
                                            <td>
                                                <a class="file-pill" target="_blank" rel="noopener"
                                                    href="{{ route('final-report.view', $report->id) }}">
                                                    Lihat
                                                </a>
                                                <a class="file-pill"
                                                    href="{{ route('final-report.download', $report->id) }}">
                                                    {{ $report->file_name }}
                                                </a>
                                            </td>
                                            <td>{{ optional($report->uploadedBy)->name ?? '-' }}</td>
                                            <td>
                                                <div><strong>Dipelajari:</strong>
                                                    {{ \Illuminate\Support\Str::limit($report->what_learned ?: '-', 90) }}
                                                </div>
                                                <div><strong>Kendala:</strong>
                                                    {{ \Illuminate\Support\Str::limit($report->challenges ?: '-', 90) }}
                                                </div>
                                                <div><strong>Solusi:</strong>
                                                    {{ \Illuminate\Support\Str::limit($report->breakthrough_solution ?: '-', 90) }}
                                                </div>
                                                <div><strong>Saran:</strong>
                                                    {{ \Illuminate\Support\Str::limit($report->suggestions_for_company ?: '-', 90) }}
                                                </div>
                                            </td>
                                            <td>{{ format_filesize($report->file_size) }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-report"
                                                    data-action="{{ route('final-report.destroy', $report->id) }}"
                                                    data-owner="{{ optional($report->user)->name ?? '-' }}"
                                                    data-file="{{ $report->file_name }}">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->

    {{-- Popup konfirmasi hapus --}}
    <div class="modal fade" id="delete-report-modal" tabindex="-1" role="dialog"
        aria-labelledby="delete-report-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form method="POST" action="" id="delete-report-form" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="delete-report-title">Hapus laporan akhir?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Laporan milik <strong id="delete-report-owner">-</strong> akan dihapus permanen
                        beserta berkasnya.</p>
                    <p class="mb-0 text-muted small" id="delete-report-file"></p>
                    <p class="mb-0 mt-2 text-danger small">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, hapus</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function() {

            $(document).on('click', '.btn-delete-report', function() {
                var $b = $(this);
                $('#delete-report-form').attr('action', $b.data('action'));
                $('#delete-report-owner').text($b.data('owner') || '-');
                $('#delete-report-file').text($b.data('file') || '');
                $('#delete-report-modal').modal('show');
            });
            $('#final-report-export-form').on('submit', function(e) {
                e.preventDefault();
                var month = $(this).find('[name="month"]').val();
                if (!month) {
                    return;
                }
                window.location.href = "{{ url('/final-report/export') }}/" + month;
            });
        });
    </script>
@endsection
