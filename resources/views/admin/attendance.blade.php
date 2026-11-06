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

        .type-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            background: #f1f6fa;
            color: #354653;
        }

        .time-cell {
            font-weight: 600;
            color: #2b2f3a;
        }

        .time-muted {
            color: #9aa4b2;
        }

        .attendance-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
        }

        .attendance-filter .form-control {
            max-width: 220px;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">{{ __('global.attendance') }}</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item"><a href="">{{ __('global.attendance') }}</a></li>


        </ol>
    </div>
@endsection
@section('button')
    <a href="attendance/assign" class="btn btn-primary btn-sm btn-flat"><i
            class="mdi mdi-plus mr-2"></i>{{ __('global.add_new') }}</a>
@endsection

@section('content')
    @include('includes.flash')
    @php
        if (!function_exists('format_duration')) {
            function format_duration($seconds)
            {
                $seconds = (int) $seconds;
                if ($seconds <= 0) {
                    return '0 detik';
                }
                $hours = (int) floor($seconds / 3600);
                $minutes = (int) floor(($seconds % 3600) / 60);
                $secs = (int) ($seconds % 60);
                $parts = [];
                if ($hours > 0) {
                    $parts[] = $hours . ' jam';
                }
                if ($minutes > 0) {
                    $parts[] = $minutes . ' menit';
                }
                if ($secs > 0) {
                    $parts[] = $secs . ' detik';
                }
                return implode(' ', $parts);
            }
        }
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-body">
                    <form method="GET" action="{{ route('attendance') }}" class="attendance-filter">
                        <input type="month" class="form-control" name="month"
                            value="{{ $selectedMonth ?? now()->format('Y-m') }}">
                        <select class="form-control" name="user_id">
                            <option value="">{{ __('global.users') }} (Semua)</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ (string) ($selectedUserId ?? '') === (string) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-sm" type="submit">Tampilkan</button>
                    </form>

                    <div class="table-rep-plugin">
                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                            <table id="datatable-buttons"
                                class="table table-striped table-bordered dt-responsive nowrap admin-table"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">

                                <thead>
                                    <tr>
                                        <th data-priority="1">{{ __('global.date') }}</th>
                                        <th data-priority="2">{{ __('global.user_id') }}</th>
                                        <th data-priority="3">{{ __('global.name') }}</th>
                                        <th data-priority="4">{{ __('global.in') }}</th>
                                        <th data-priority="5">{{ __('global.out') }}</th>
                                        <th data-priority="6">{{ __('global.status') }}</th>
                                        <th data-priority="7">{{ __('global.note') }}</th>


                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupedLogs as $weekKey => $weekLogs)
                                        @if ($weekKey === 'unknown')
                                            @continue
                                        @endif
                                        @php
                                            $parts = explode('-', $weekKey);
                                            $weekYear = (int) ($parts[0] ?? 0);
                                            $weekNumber = (int) ($parts[2] ?? 0);
                                        @endphp
                                        <tr class="table-active">
                                            <td colspan="7"><strong>Minggu ke-{{ $weekNumber }}</strong>
                                                ({{ $weekYear }})</td>
                                        </tr>
                                        @foreach ($weekLogs as $log)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($log['date'])->format('d-m-Y') }}</td>
                                                <td>{{ $log['user_id'] }}</td>
                                                <td>{{ $log['name'] }}</td>
                                                @php
                                                    $isIn = $log['type'] === 'Masuk';
                                                    $isOut = $log['type'] === 'Pulang';
                                                @endphp
                                                <td>
                                                    @if ($isIn)
                                                        <span class="type-pill"><i class="fa fa-sign-in-alt"></i>
                                                            {{ $log['time'] }}</span>
                                                    @else
                                                        <span class="time-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($isOut)
                                                        <span class="type-pill"><i class="fa fa-sign-out-alt"></i>
                                                            {{ $log['time'] }}</span>
                                                    @else
                                                        <span class="time-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $status = $log['status'];
                                                        $diffSeconds = $log['diff_seconds'];
                                                        $duration = $log['duration'];
                                                    @endphp
                                                    @if ($status === 'Terlambat')
                                                        <span
                                                            class="badge badge-danger badge-pill">{{ __('global.late') }}</span>
                                                        @if (!is_null($diffSeconds))
                                                            <div class="text-danger small">Terlambat
                                                                {{ format_duration($diffSeconds) }}</div>
                                                        @elseif (!is_null($duration))
                                                            <div class="text-danger small">Terlambat {{ $duration }}
                                                            </div>
                                                        @endif
                                                    @elseif ($status === 'Pulang Cepat')
                                                        <span
                                                            class="badge badge-danger badge-pill">{{ __('global.too_fast') }}</span>
                                                        <div class="text-danger small">Pulang lebih cepat
                                                            {{ format_duration($diffSeconds) }}</div>
                                                    @else
                                                        <span
                                                            class="badge badge-primary badge-pill">{{ __('global.on_time') }}</span>
                                                        @if (!is_null($diffSeconds) && $diffSeconds > 0)
                                                            @if ($log['type'] === 'Masuk')
                                                                <div class="text-success small">Lebih cepat
                                                                    {{ format_duration($diffSeconds) }}</div>
                                                            @elseif ($log['type'] === 'Pulang')
                                                                <div class="text-success small">Pulang lebih lama
                                                                    {{ format_duration($diffSeconds) }}</div>
                                                            @endif
                                                        @else
                                                            <div class="text-success small">Tepat waktu</div>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>{{ $log['note'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
@endsection


@section('script')
    <!-- Responsive-table-->
    <script src="{{ URL::asset('plugins/RWD-Table-Patterns/dist/js/rwd-table.min.js') }}"></script>
@endsection

@section('script')
    <script>
        $(function() {
            $('.table-responsive').responsiveTable({
                addDisplayAllBtn: 'btn btn-secondary'
            });
        });
    </script>
@endsection
