@php
    $activeTab = $active ?? '';
    $isHomeActive = $activeTab === 'home';
    $isInternshipReportActive = in_array($activeTab, ['monthly', 'final'], true);
    $isAttendanceHistoryActive = in_array($activeTab, ['history', 'history_draft'], true);
    $isAbsenceActive = in_array($activeTab, ['absence', 'absence_izin', 'absence_lupa'], true);

    $csTitles = [
        'home' => 'Home',
        'attendance' => 'Kehadiran',
        'history' => 'Kalender Kehadiran',
        'history_draft' => 'Upload Draft Presensi',
        'absence' => 'Ketidakhadiran',
        'absence_izin' => 'Izin & Sakit',
        'absence_lupa' => 'Lupa Absensi',
        'monthly' => 'Laporan Bulanan',
        'final' => 'Laporan Akhir',
        'profile' => 'Profil',
        'internship_templates' => 'Draft Dokumen Magang',
        'worklog' => 'Log Pekerjaan',
    ];
    $csPageTitle = $csTitles[$activeTab] ?? 'Portal Peserta';

    $csUser = auth()->user();
    $csName = $csUser->name ?? 'Peserta';
    $csInitials = '';
    foreach (array_slice(array_filter(preg_split('/\s+/', trim($csName))), 0, 2) as $csWord) {
        $csInitials .= mb_strtoupper(mb_substr($csWord, 0, 1));
    }
    $csPhoto = $csUser->profile_photo_path ?? null;
    $csToday = \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y');
@endphp

<script>
    document.body.classList.add('cs-portal');
</script>

<aside class="cs-sidebar" id="cs-sidebar">
    <a href="{{ route('home') }}" class="cs-brand">
        @include('layouts.brand-logo')
    </a>

    <div class="cs-nav-label">Workspace</div>
    <a class="cs-side-link {{ $isHomeActive ? 'active' : '' }}" href="{{ route('home') }}">
        <i class="mdi mdi-home-outline"></i> Home
    </a>
    <a class="cs-side-link {{ $activeTab === 'attendance' ? 'active' : '' }}"
        href="{{ route('user.attendance.index') }}">
        <i class="mdi mdi-clock-outline"></i> Kehadiran
    </a>

    <a class="cs-side-link {{ $activeTab === 'worklog' ? 'active' : '' }}"
        href="{{ route('user.work-logs.index') }}">
        <i class="mdi mdi-briefcase-check-outline"></i> Log Pekerjaan
    </a>

    <div class="cs-side-group {{ $isAttendanceHistoryActive ? 'has-active' : '' }}">
        <button type="button" class="cs-side-toggle {{ $isAttendanceHistoryActive ? 'active' : '' }}">
            <i class="mdi mdi-clipboard-text-outline cs-ic"></i> Riwayat Kehadiran
            <span class="cs-side-caret">&#9662;</span>
        </button>
        <div class="cs-side-sub">
            <a class="{{ $activeTab === 'history' ? 'active' : '' }}"
                href="{{ route('user.attendance.history') }}">Kalender Kehadiran</a>
            <a class="{{ $activeTab === 'history_draft' ? 'active' : '' }}"
                href="{{ route('user.attendance.draft') }}">Upload Draft Presensi</a>
        </div>
    </div>

    <div class="cs-side-group {{ $isAbsenceActive ? 'has-active' : '' }}">
        <button type="button" class="cs-side-toggle {{ $isAbsenceActive ? 'active' : '' }}">
            <i class="mdi mdi-clipboard-text-outline cs-ic"></i> Ketidakhadiran
            <span class="cs-side-caret">&#9662;</span>
        </button>
        <div class="cs-side-sub">
            <a class="{{ $activeTab === 'absence_izin' ? 'active' : '' }}"
                href="{{ route('user.absence.index', ['type' => 'izin_sakit']) }}">Izin &amp; Sakit</a>
            <a class="{{ $activeTab === 'absence_lupa' ? 'active' : '' }}"
                href="{{ route('user.absence.index', ['type' => 'lupa_absensi']) }}">Lupa Absensi</a>
        </div>
    </div>

    <div class="cs-nav-label is-spaced">Pelaporan</div>
    <div class="cs-side-group {{ $isInternshipReportActive ? 'has-active' : '' }}">
        <button type="button" class="cs-side-toggle {{ $isInternshipReportActive ? 'active' : '' }}">
            <i class="mdi mdi-file-document-outline cs-ic"></i> Laporan Magang
            <span class="cs-side-caret">&#9662;</span>
        </button>
        <div class="cs-side-sub">
            <a class="{{ $activeTab === 'monthly' ? 'active' : '' }}"
                href="{{ route('user.monthly-report') }}">Laporan Bulanan</a>
            <a class="{{ $activeTab === 'final' ? 'active' : '' }}"
                href="{{ route('user.final-report') }}">Laporan Akhir</a>
        </div>
    </div>
</aside>
<div class="cs-overlay" id="cs-overlay"></div>

<header class="cs-topbar">
    <div style="display:flex;align-items:center;">
        <button type="button" class="cs-menu-btn" id="cs-menu-btn" aria-label="Buka menu">
            <i class="mdi mdi-menu"></i>
        </button>
        <div class="cs-crumb">Portal Peserta <span>/</span> <strong>{{ $csPageTitle }}</strong></div>
    </div>
    <div class="cs-topbar-right">
        <span class="cs-date">{{ $csToday }}</span>
        <div class="dropdown cs-user-menu">
            <a class="dropdown-toggle arrow-none cs-avatar-toggle" data-toggle="dropdown" href="#" role="button"
                aria-haspopup="true" aria-expanded="false" aria-label="Menu akun">
                @if ($csPhoto)
                    <img src="{{ asset('storage/' . $csPhoto) }}" alt="{{ $csName }}">
                @else
                    <span>{{ $csInitials ?: 'P' }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                <a class="dropdown-item {{ $activeTab === 'profile' ? 'active' : '' }}"
                    href="{{ route('user.profile') }}">
                    @if ($csPhoto)
                        <img src="{{ asset('storage/' . $csPhoto) }}" alt="Profil" class="rounded-circle m-r-5"
                            style="width: 20px; height: 20px; object-fit: cover;">
                    @else
                        <i class="mdi mdi-account-circle m-r-5"></i>
                    @endif
                    Profil
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('cs-logout-form').submit();">
                    <i class="mdi mdi-power text-danger"></i> {{ __('global.logout') }}
                </a>
                <form id="cs-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>

@once
    <script>
        (function() {
            var body = document.body;
            var btn = document.getElementById('cs-menu-btn');
            var overlay = document.getElementById('cs-overlay');

            function closeMenu() {
                body.classList.remove('cs-open');
                body.style.overflow = '';
            }
            if (btn) {
                btn.addEventListener('click', function() {
                    body.classList.toggle('cs-open');
                    body.style.overflow = body.classList.contains('cs-open') && window.innerWidth <= 991 ?
                        'hidden' : '';
                });
            }
            if (overlay) {
                overlay.addEventListener('click', closeMenu);
            }
            document.querySelectorAll('.cs-side-toggle').forEach(function(el) {
                el.addEventListener('click', function() {
                    var group = el.closest('.cs-side-group');
                    if (!group) return;
                    var shown = group.classList.contains('is-open') || group.classList.contains('has-active');
                    if (shown) {
                        group.classList.remove('is-open', 'has-active');
                    } else {
                        group.classList.add('is-open');
                    }
                });
            });
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    closeMenu();
                }
            });
        })();
    </script>
@endonce
