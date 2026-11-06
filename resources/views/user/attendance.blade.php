@extends('layouts.master-blank')

@section('content')
@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=1" rel="stylesheet" type="text/css" />
@endsection
@php
    $displayUser = auth()->user();
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
@include('layouts.user-navbar', ['active' => 'attendance'])
<div class="attendance-page">
    <div class="attendance-shell">
        <div class="attendance-topbar">
            <div class="attendance-greeting">
                <span>Selamat datang kembali</span>
                <h1>{{ $displayUser->name }}</h1>
            </div>
        </div>

        <div class="hero-card">
            <div>
                <div class="hero-title">{{ __('global.attendance') }}</div>
                <div class="hero-subtitle">{{ __('global.record_attendance') }}</div>
                <div class="status-chips">
                    <span class="chip">Masuk {{ \App\Models\Schedule::timeInShort() }}</span>
                    <span class="chip accent">Pulang {{ \App\Models\Schedule::timeOutShort() }}</span>
                </div>
            </div>
            <div class="clock-panel">
                <div class="clock-time" id="clock">--:--:--</div>
                <div class="text-muted">{{ __('global.current_time') }}</div>
                @if ($lastAttendance)
                    <div class="small text-muted">
                        {{ __('global.last_time_in') }}:
                        {{ \Carbon\Carbon::parse($lastAttendance->attendance_date)->format('d-m-Y') }}
                        {{ $lastAttendance->attendance_time }}
                    </div>
                @endif
                @if ($lastLeave)
                    <div class="small text-muted">
                        {{ __('global.last_time_out') }}:
                        {{ \Carbon\Carbon::parse($lastLeave->leave_date)->format('d-m-Y') }}
                        {{ $lastLeave->leave_time }}
                    </div>
                @endif
            </div>
        </div>

        <div class="actions-grid">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-title">Masuk</div>
                    <span class="action-status {{ $hasAttendance ? 'done' : 'pending' }}">
                        {{ $hasAttendance ? 'Tercatat' : 'Belum' }}
                    </span>
                </div>
                @if ($hasAttendance)
                    <div class="alert alert-info mb-2">
                        {{ __('global.time_in_recorded') }}
                    </div>
                    @php
                        $timeIn = \App\Models\Schedule::FIXED_TIME_IN;
                        $attendanceTime = date('H:i:s', strtotime($lastAttendance->attendance_time ?? '00:00:00'));
                        $scheduleTs = strtotime(($lastAttendance->attendance_date ?? date('Y-m-d')) . ' ' . $timeIn);
                        $attendanceTs = strtotime(
                            ($lastAttendance->attendance_date ?? date('Y-m-d')) . ' ' . $attendanceTime,
                        );
                        $diffSeconds = abs($attendanceTs - $scheduleTs);
                        $isLate = $attendanceTs > $scheduleTs;
                    @endphp
                    @if ($isLate)
                        <div class="note-status text-danger">Terlambat {{ format_duration($diffSeconds) }}</div>
                    @else
                        <div class="note-status text-success">
                            @if ($diffSeconds > 0)
                                Lebih cepat {{ format_duration($diffSeconds) }}
                            @else
                                Tepat waktu
                            @endif
                        </div>
                    @endif
                @else
                    <form method="POST" action="{{ route('home.timein.store') }}" class="action-form">
                        @csrf
                        <div class="form-group">
                            <label for="note_time_in">{{ __('global.note_optional') }}</label>
                            <input type="text" class="form-control" id="note_time_in" name="note"
                                placeholder="Contoh: macet / izin">
                        </div>
                        <button class="btn btn-primary action-button" type="submit">
                            {{ __('global.time_in') }}
                        </button>
                    </form>
                    <div id="realtime-timein" class="note-status"></div>
                @endif
            </div>

            <div class="action-card">
                <div class="action-header">
                    <div class="action-title">Pulang</div>
                    <span class="action-status {{ $hasLeave ? 'done' : 'pending' }}">
                        {{ $hasLeave ? 'Tercatat' : 'Belum' }}
                    </span>
                </div>
                @if ($hasLeave)
                    <div class="alert alert-info mb-2">
                        {{ __('global.time_out_recorded') }}
                    </div>
                    @php
                        $timeOut = \App\Models\Schedule::FIXED_TIME_OUT;
                        $leaveTime = date('H:i:s', strtotime($lastLeave->leave_time ?? '00:00:00'));
                        $scheduleTs = strtotime(($lastLeave->leave_date ?? date('Y-m-d')) . ' ' . $timeOut);
                        $leaveTs = strtotime(($lastLeave->leave_date ?? date('Y-m-d')) . ' ' . $leaveTime);
                        $diffSeconds = abs($leaveTs - $scheduleTs);
                        $isEarly = $leaveTs < $scheduleTs;
                    @endphp
                    @if ($isEarly)
                        <div class="note-status text-danger">Pulang lebih cepat {{ format_duration($diffSeconds) }}
                        </div>
                    @else
                        <div class="note-status text-success">
                            @if ($diffSeconds > 0)
                                Pulang lebih lama {{ format_duration($diffSeconds) }}
                            @else
                                Tepat waktu
                            @endif
                        </div>
                    @endif
                @elseif (!$hasAttendance)
                    <div class="alert alert-warning mb-0">
                        Absen masuk dahulu sebelum absen pulang.
                    </div>
                @else
                    <form method="POST" action="{{ route('home.timeout.store') }}" class="action-form">
                        @csrf
                        <div class="form-group">
                            <label for="note_time_out">{{ __('global.note_optional') }}</label>
                            <input type="text" class="form-control" id="note_time_out" name="note"
                                placeholder="Contoh: izin / keperluan mendadak">
                        </div>
                        <button class="btn btn-danger action-button" type="submit">
                            {{ __('global.time_out') }}
                        </button>
                    </form>
                    <div id="realtime-timeout" class="note-status"></div>
                @endif
            </div>

        </div>

        <div class="history-shortcut">
            <div>
                <strong>Riwayat Kehadiran</strong>
                <div class="text-muted small">Lihat log masuk dan pulang secara lengkap.</div>
            </div>
            <a href="{{ route('user.attendance.history') }}" class="btn btn-outline-primary btn-sm">Buka Riwayat</a>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function() {
        var clock = document.getElementById('clock');
        var realtimeTimeIn = document.getElementById('realtime-timein');
        var realtimeTimeOut = document.getElementById('realtime-timeout');

        function formatDuration(seconds) {
            seconds = Math.max(0, Math.floor(seconds));
            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = seconds % 60;
            var parts = [];
            if (hours > 0) {
                parts.push(hours + ' jam');
            }
            if (minutes > 0) {
                parts.push(minutes + ' menit');
            }
            if (secs > 0) {
                parts.push(secs + ' detik');
            }
            return parts.length ? parts.join(' ') : '0 detik';
        }

        function updateRealtimeStatus(now) {
            if (realtimeTimeIn) {
                var startIn = new Date(now);
                startIn.setHours(8, 0, 0, 0);
                var diffIn = Math.abs(now - startIn) / 1000;
                if (now > startIn) {
                    realtimeTimeIn.textContent = 'Terlambat ' + formatDuration(diffIn);
                    realtimeTimeIn.className = 'text-danger small mt-2';
                } else if (now.getTime() === startIn.getTime()) {
                    realtimeTimeIn.textContent = 'Tepat waktu';
                    realtimeTimeIn.className = 'text-success small mt-2';
                } else {
                    realtimeTimeIn.textContent = 'Masih bisa tepat waktu (' + formatDuration(diffIn) + ' lagi)';
                    realtimeTimeIn.className = 'text-success small mt-2';
                }
            }

            if (realtimeTimeOut) {
                var startOut = new Date(now);
                startOut.setHours(16, 30, 0, 0);
                var diffOut = Math.abs(now - startOut) / 1000;
                if (now >= startOut) {
                    realtimeTimeOut.textContent = 'Boleh pulang (' + formatDuration(diffOut) + ' lewat)';
                    realtimeTimeOut.className = 'text-success small mt-2';
                } else {
                    realtimeTimeOut.textContent = 'Belum waktu pulang (' + formatDuration(diffOut) + ' lagi)';
                    realtimeTimeOut.className = 'text-danger small mt-2';
                }
            }
        }

        function tick() {
            var now = new Date();
            var h = String(now.getHours()).padStart(2, '0');
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            clock.textContent = h + ':' + m + ':' + s;
            updateRealtimeStatus(now);
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endsection
