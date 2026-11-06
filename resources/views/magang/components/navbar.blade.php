<header class="cs-pub-header">
    <div class="cs-pub-header-inner">
        <a href="{{ route('welcome') }}" class="cs-brand">
            @include('layouts.brand-logo')
        </a>

        {{-- Desktop --}}
        <nav class="cs-pub-nav">
            <a href="{{ route('welcome') }}#program">Program</a>
            <a href="{{ route('welcome') }}#tentang">Tentang Kami</a>
            <a href="{{ route('welcome') }}#faq">FAQ</a>
            <a href="{{ route('magang.cek-status') }}">Portal Peserta</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('magang.form') }}" class="cs-btn">Daftar Magang</a>
        </nav>

        {{-- Tombol menu mobile --}}
        <button type="button" onclick="toggleMobileNav()" class="cs-pub-toggle" aria-label="Toggle menu">
            <span id="navIconOpen">&#9776;</span>
            <span id="navIconClose" style="display:none;">&#10005;</span>
        </button>
    </div>

    {{-- Menu mobile --}}
    <div id="mobileNavMenu" class="cs-pub-mobile">
        <a href="{{ route('welcome') }}#program">Program</a>
        <a href="{{ route('welcome') }}#tentang">Tentang Kami</a>
        <a href="{{ route('welcome') }}#faq">FAQ</a>
        <a href="{{ route('magang.cek-status') }}">Portal Peserta</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('magang.form') }}">Daftar Magang</a>
    </div>
</header>

<script>
    document.querySelectorAll('#mobileNavMenu a').forEach(function(a) {
        a.addEventListener('click', function() {
            var menu = document.getElementById('mobileNavMenu');
            if (menu.classList.contains('is-open')) toggleMobileNav();
        });
    });

    function toggleMobileNav() {
        var menu = document.getElementById('mobileNavMenu');
        var iconOpen = document.getElementById('navIconOpen');
        var iconClose = document.getElementById('navIconClose');
        var open = menu.classList.toggle('is-open');
        iconOpen.style.display = open ? 'none' : '';
        iconClose.style.display = open ? '' : 'none';
    }
</script>
