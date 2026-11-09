<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PLN Suku Cadang')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="{{ asset('css/cerah.css') }}?v=3" rel="stylesheet">
    <link href="{{ asset('css/cerah-public.css') }}?v=1" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Nama "pln" dipertahankan agar kelas yang sudah ada tetap bekerja,
                        // nilainya kini mengikuti palet Cerah.
                        pln: {
                            green: '#1b6aa8',
                            dark: '#134f82',
                            yellow: '#a7e8ea',
                            light: '#e3f2fa',
                            blue: '#1b6aa8',
                        },
                        // Hijau = sukses (tetap hijau agar status mudah dibedakan)
                        green: {
                            50: '#f4f9e4', 100: '#eaf3d1', 200: '#dfeabb', 300: '#c9de92', 400: '#a9c75a',
                            500: '#8aa83a', 600: '#6d8a2a', 700: '#55691f', 800: '#44541b', 900: '#38461a',
                        },
                        // Biru/indigo = informasi (biru PLN)
                        blue: {
                            50: '#eef7fc', 100: '#e1f0f9', 200: '#c8e3f2', 300: '#9fcde8', 400: '#62aad8',
                            500: '#3689c4', 600: '#1b6aa8', 700: '#134f82', 800: '#0f3d63', 900: '#0b2f4e',
                        },
                        indigo: {
                            50: '#eef7fc', 100: '#e1f0f9', 200: '#c8e3f2', 300: '#9fcde8', 400: '#62aad8',
                            500: '#3689c4', 600: '#1b6aa8', 700: '#134f82', 800: '#0f3d63', 900: '#0b2f4e',
                        },
                        // Kuning = menunggu (amber hangat)
                        yellow: {
                            50: '#fff9e8', 100: '#fff4d8', 200: '#f6e5b2', 300: '#ecd283', 400: '#dcb64f',
                            500: '#c49a38', 600: '#a37f2b', 700: '#86671f', 800: '#6b5219', 900: '#574216',
                        },
                        // Merah = ditolak / error (rose lembut)
                        red: {
                            50: '#fdf3f6', 100: '#fae8ed', 200: '#f1cfd8', 300: '#e6aebd', 400: '#d6849a',
                            500: '#c0566f', 600: '#aa4660', 700: '#8d3a50', 800: '#702f41', 900: '#5c2736',
                        },
                        // Netral kebiruan mengikuti warna teks/garis tema
                        slate: {
                            50: '#f6fafc', 100: '#e8f1f6', 200: '#d9e7ef', 300: '#c9dbe6', 400: '#94a8b8',
                            500: '#5b7184', 600: '#44596b', 700: '#2a3f55', 800: '#1c3349', 900: '#0f2a43',
                        }
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'system-ui', 'sans-serif'],
                        poppins: ['Manrope', 'DM Sans', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    {{-- Global Responsive CSS --}}
    <style>
        /* Prevent horizontal overflow globally */
        /* 'clip' (bukan 'hidden') agar header sticky tetap menempel saat scroll */
        html,
        body {
            overflow-x: clip;
            max-width: 100vw;
        }

        /* Responsive font scaling */
        @media (max-width: 767px) {
            html {
                font-size: 15px;
            }

            /* Full-width buttons on mobile */
            .btn-mobile-full {
                width: 100% !important;
                display: block !important;
            }

            /* Ensure touch-friendly tap targets */
            button,
            a,
            input,
            select,
            textarea {
                min-height: 40px;
            }

            /* Reduce padding on containers */
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            html {
                font-size: 15.5px;
            }
        }

        @media (min-width: 992px) {
            html {
                font-size: 16px;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Better image handling */
        img {
            max-width: 100%;
            height: auto;
        }
    </style>

    @stack('styles')
</head>

<body class="cs-pub flex flex-col min-h-screen text-slate-800 font-sans">

    @include('magang.components.navbar')

    @include('magang.components.notification')

    <main class="flex-grow">
        @yield('content')
    </main>

    @include('magang.components.footer')

    @stack('scripts')
    @include('includes.dialog')
</body>

</html>
