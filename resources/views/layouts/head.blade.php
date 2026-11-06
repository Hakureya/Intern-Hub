<!-- App favicon -->
<link rel="shortcut icon" href="{{ URL::asset('assets/images/') }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
@yield('css')

<!-- App css -->
<link href="{{ URL::asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/metismenu.min.css') }}" rel="stylesheet" type="text/css">
<style>
    .logo .logo-text,
    .logo .logo-text-mini {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .logo .logo-main-img {
        max-height: 34px;
        width: auto;
        display: block;
    }

    .logo .logo-mini-img {
        max-height: 16px;
        max-width: 58px;
        width: auto;
        display: block;
    }
</style>
<link href="{{ URL::asset('assets/css/icons.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/style.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/mobile-overrides.css') }}" rel="stylesheet" type="text/css" />

<!-- Cerah theme: font + design system -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="{{ URL::asset('css/cerah.css') }}?v=3" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('css/cerah-admin.css') }}?v=1" rel="stylesheet" type="text/css" />

{{-- <link href="{{ URL::asset('plugins/sweet-alert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css"> --}}
<link href="{{ asset('plugins/sweetalert.min.css') }}" rel="stylesheet">
<!-- Table css -->
<link href="{{ URL::asset('plugins/RWD-Table-Patterns/dist/css/rwd-table.min.css') }}" rel="stylesheet" type="text/css"
    media="screen">
<!-- DataTables -->
<link href="{{ URL::asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Responsive datatable examples -->
<link href="{{ URL::asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />

<style>
    .topbar .topbar-left {
        overflow: hidden;
        border-bottom: 0 !important;
    }

    .side-menu {
        border-top: 0 !important;
    }

    .topbar .topbar-left .logo {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 70px;
        line-height: 1;
        margin: 0;
    }

    .topbar .topbar-left .logo .logo-text {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .topbar .topbar-left .logo .logo-main-img {
        max-height: 30px;
        width: auto;
    }

    .enlarged #wrapper .topbar .topbar-left .logo i {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 70px;
        line-height: 1 !important;
        color: inherit !important;
        padding: 0;
    }

    .enlarged #wrapper .topbar .topbar-left .logo i .logo-mini-img {
        max-height: 20px;
        max-width: 62px;
        width: auto;
        margin: 0 auto;
    }
</style>
