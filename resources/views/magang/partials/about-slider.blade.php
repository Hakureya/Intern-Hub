{{-- Slider "Tentang Kami". Isi dikelola super admin lewat menu Manajemen Slide. --}}
@php
    $aboutSlides = \App\Models\LandingSlide::forLanding();
@endphp
@if ($aboutSlides->isNotEmpty())
    @php
        $slideSeconds = max(2, min(30, (int) \App\Models\LandingSetting::read('slide_interval', 6)));
        $slideAutoplay = (string) \App\Models\LandingSetting::read('slide_autoplay', '1') === '1';
    @endphp
    <section class="cs-section cs-why" id="tentang" aria-roledescription="carousel" aria-label="Tentang kami"
        data-interval="{{ $slideSeconds * 1000 }}" data-autoplay="{{ $slideAutoplay ? '1' : '0' }}">
        <div class="cs-slides" id="aboutSlides">
            @foreach ($aboutSlides as $i => $slide)
                @include('magang.partials.about-slide', ['slide' => $slide, 'index' => $i, 'total' => $aboutSlides->count()])
            @endforeach
        </div>

        @if ($aboutSlides->count() > 1)
            <button type="button" class="cs-arrow cs-arrow-prev" aria-label="Slide sebelumnya">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button type="button" class="cs-arrow cs-arrow-next" aria-label="Slide berikutnya">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            <div class="cs-slider-dots" role="tablist" aria-label="Pilih slide">
                @foreach ($aboutSlides as $i => $slide)
                    <button type="button" class="cs-dot{{ $i === 0 ? ' is-active' : '' }}"
                        aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        (function() {
            var section = document.getElementById('tentang');
            if (!section) return;
            var slides = section.querySelectorAll('.cs-slide');
            var dots = section.querySelectorAll('.cs-dot');
            var current = 0,
                timer = null,
                INTERVAL = parseInt(section.getAttribute('data-interval'), 10) || 6000,
                AUTOPLAY = section.getAttribute('data-autoplay') === '1' && slides.length > 1;
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function go(i) {
                current = (i + slides.length) % slides.length;
                slides.forEach(function(s, n) {
                    s.classList.toggle('is-active', n === current);
                    s.setAttribute('aria-hidden', n === current ? 'false' : 'true');
                });
                dots.forEach(function(d, n) {
                    d.classList.toggle('is-active', n === current);
                    d.setAttribute('aria-selected', n === current ? 'true' : 'false');
                });
            }

            function start() {
                stop();
                if (reduceMotion || !AUTOPLAY) return;
                timer = setInterval(function() {
                    go(current + 1);
                }, INTERVAL);
            }

            function stop() {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            }

            dots.forEach(function(d, n) {
                d.addEventListener('click', function() {
                    go(n);
                    start();
                });
            });

            var prevBtn = section.querySelector('.cs-arrow-prev');
            var nextBtn = section.querySelector('.cs-arrow-next');
            if (prevBtn) prevBtn.addEventListener('click', function() {
                go(current - 1);
                start();
            });
            if (nextBtn) nextBtn.addEventListener('click', function() {
                go(current + 1);
                start();
            });

            // Jeda saat disentuh / di-hover / difokuskan agar nyaman dibaca
            section.addEventListener('mouseenter', stop);
            section.addEventListener('mouseleave', start);
            section.addEventListener('focusin', stop);
            section.addEventListener('focusout', start);

            // Geser di layar sentuh
            var startX = null;
            section.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                stop();
            }, {
                passive: true
            });
            section.addEventListener('touchend', function(e) {
                if (startX !== null && slides.length > 1) {
                    var dx = e.changedTouches[0].clientX - startX;
                    if (Math.abs(dx) > 50) go(current + (dx < 0 ? 1 : -1));
                }
                startX = null;
                start();
            });

            // Hemat sumber daya saat tab tidak terlihat
            document.addEventListener('visibilitychange', function() {
                document.hidden ? stop() : start();
            });

            go(0);
            start();
        })();
    </script>
@endif
