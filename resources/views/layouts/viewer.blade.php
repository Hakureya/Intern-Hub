<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <title>Log Pekerjaan</title>
    @include('layouts.head')
    <style>
        /* Tanpa sidebar: konten selebar layar */
        .content-page {
            margin-left: 0 !important;
        }

        /* Layout Topbar Flexbox agar selalu sejajar rapi */
        .topbar {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 24px;
        }

        .topbar .topbar-left {
            position: static !important;
            width: auto !important;
            float: none !important;
            background: transparent !important;
            display: flex;
            align-items: center;
            padding-right: 20px;
            margin-right: 16px;
            border-right: 1px solid #e9eef2;
            height: 36px;
        }

        .topbar .topbar-left .logo {
            line-height: normal !important;
            display: flex;
            align-items: center;
        }

        .navbar-custom {
            margin-left: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            padding: 0 !important;
        }

        /* Tab Menu Aktif */
        .viewer-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 70px;
            padding: 0 12px;
            font-weight: 700;
            font-size: 14px;
            color: var(--cs-primary, #1b6aa8);
            border-bottom: 3px solid var(--cs-primary, #1b6aa8);
            text-decoration: none !important;
            transition: opacity 0.2s ease;
        }

        .viewer-tab i {
            font-size: 18px;
        }

        .viewer-tab:hover {
            opacity: 0.85;
            color: var(--cs-primary, #1b6aa8);
        }

        /* Bagian Kanan (Profil & Logout) */
        .navbar-right-group {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .viewer-user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .viewer-who {
            font-size: 11.5px;
            color: #6d808e;
            line-height: 1.3;
            text-align: right;
        }

        .viewer-who b {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: #243642;
        }

        .viewer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(27, 106, 168, 0.1);
            color: var(--cs-primary, #1b6aa8);
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .btn-logout-topbar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            color: #dc3545;
            background: rgba(220, 53, 69, 0.08);
            transition: all 0.2s ease;
            text-decoration: none !important;
        }

        .btn-logout-topbar:hover {
            background: #dc3545;
            color: #ffffff;
        }

        /* Responsif untuk layar HP */
        @media (max-width: 576px) {
            .topbar {
                padding: 0 14px;
            }
            .topbar .topbar-left {
                padding-right: 12px;
                margin-right: 8px;
            }
            .viewer-who {
                display: none;
            }
            .viewer-tab span {
                font-size: 13px;
            }
        }
    </style>
</head>

<body class="cs-theme">
    <div id="wrapper">
        <div class="topbar">
            <!-- Brand / Logo -->
            <div class="topbar-left">
                <a href="{{ route('viewer.work-logs.index') }}" class="logo">
                    <span class="logo-text">
                        <span class="cs-brand">@include('layouts.brand-logo')</span>
                    </span>
                    <i class="logo-text-mini">
                        <span class="cs-brand-mark">
                            <img src="{{ asset('assets/images/logo-pln1.png') }}" alt="Logo PLN">
                        </span>
                    </i>
                </a>
            </div>

            <!-- Navigasi & Info User -->
            <nav class="navbar-custom">
                <!-- Menu Kiri -->
                <div class="d-flex align-items-center">
                    <a class="viewer-tab" href="{{ route('viewer.work-logs.index') }}">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <span>Log Pekerjaan</span>
                    </a>
                </div>

                <!-- Info User & Logout Kanan -->
                <ul class="navbar-right-group">
                    <li class="viewer-user-badge">
                        <div class="viewer-who">
                            <b>{{ Auth::user()->name }}</b>
                            <span>Divisi {{ optional(Auth::user()->division)->name ?: '-' }}</span>
                        </div>
                        <div class="viewer-avatar" title="{{ Auth::user()->name }}">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    </li>
                    <li>
                        <a class="btn btn-danger btn-sm" href="{{ route('logout') }}" title="{{ __('global.logout') }}"
                            style="padding: 6px 14px; font-weight: 600;"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Keluar
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>
        @include('layouts.footer-script')
    </div>
    @include('includes.flash')
</body>

</html>