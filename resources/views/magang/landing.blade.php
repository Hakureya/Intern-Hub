@extends('magang.layout.app')

@section('title', 'Program Magang | PLN Suku Cadang')

@section('content')

    {{-- HERO --}}
    <section class="cs-hero">
        <div class="cs-hero-copy animate-fade-in-up">
            @if (session('error'))
                <div class="cs-pub-alert is-error" style="margin:0 0 24px;padding:0;" role="alert">
                    <div>
                        <span class="cs-pub-alert-icon">!</span>
                        <div>
                            <strong>Maaf!</strong>
                            {{ session('error') }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="cs-eyebrow"><span class="cs-eyebrow-dot"></span>{{ $batchInfo ?? 'Program Magang Batch 2025/2026' }}</div>

            <h1>
                <span class="is-muted">Wujudkan potensi profesionalmu bersama</span>
                <span class="cs-accent">PLN Suku Cadang.</span>
            </h1>

            <p class="cs-hero-desc">
                Bergabunglah dalam Program Magang intensif untuk mahasiswa dan fresh graduate.
                Dapatkan pengalaman nyata di industri ketenagalistrikan nasional.
            </p>

            <div class="cs-hero-actions">
                <a href="{{ route('magang.form') }}" class="cs-btn">Daftar Sekarang <span>→</span></a>
                <button type="button" onclick="openModal()" class="cs-btn cs-btn-outline">Cek Status</button>
            </div>
        </div>

        <div class="cs-hero-art" aria-hidden="true">
            <div class="cs-art-grid"></div>
            <div class="cs-orb is-one"></div>
            <div class="cs-orb is-two"></div>

            <div class="cs-hero-photo">
                <img src="{{ asset('images/magang/foto/hero-peserta.png') }}" alt="Peserta magang PLN Suku Cadang"
                    loading="eager" onerror="this.remove()">
            </div>

            <div class="cs-float-card is-top">
                <span class="cs-mini-icon">🚀</span>
                <div><strong>Real Project</strong><small>Pengalaman langsung</small></div>
            </div>
            <div class="cs-float-card is-bottom">
                <span class="cs-mini-icon is-aqua">📜</span>
                <div><strong>Sertifikat Resmi</strong><small>Dari PLN Suku Cadang</small></div>
            </div>
        </div>
    </section>

    {{-- KEUNTUNGAN --}}
    <section class="cs-section" id="program">
        <div class="cs-section-heading">
            <h2>Yang kamu dapatkan<br><span class="cs-accent">selama magang.</span></h2>
            <p>Program magang dirancang agar kamu belajar langsung dari pekerjaan nyata, bukan sekadar pengamat.</p>
        </div>

        <div class="cs-program-grid">
            <article class="cs-program-card is-sky">
                <div class="cs-program-top"><span>01</span><span>↗</span></div>
                <div class="cs-program-img"><img src="{{ asset('images/magang/foto/RealProject.jpg') }}" alt="Real Project" loading="lazy"></div>
                <h3>Real Project</h3>
                <p>Terlibat langsung dalam pengembangan sistem internal perusahaan.</p>
            </article>

            <article class="cs-program-card is-ice">
                <div class="cs-program-top"><span>02</span><span>↗</span></div>
                <div class="cs-program-img"><img src="{{ asset('images/magang/foto/Mentorship.jpg') }}" alt="Mentorship" loading="lazy"></div>
                <h3>Mentorship</h3>
                <p>Bimbingan intensif langsung dari para profesional ahli di bidangnya.</p>
            </article>

            <article class="cs-program-card is-aqua">
                <div class="cs-program-top"><span>03</span><span>↗</span></div>
                <div class="cs-program-img"><img src="{{ asset('images/magang/foto/SoftSkill.jpg') }}" alt="Peningkatan Soft Skills" loading="lazy"></div>
                <h3>Peningkatan Soft Skills</h3>
                <p>Mengasah komunikasi profesional, kerja sama tim, adaptasi, dan problem solving.</p>
            </article>
        </div>
    </section>

    {{-- TENTANG KAMI (slider) — dikelola lewat Manajemen Slide --}}
    @include('magang.partials.about-slider')

    {{-- ALUR SELEKSI (sama dengan timeline di halaman Portal Peserta) --}}
    <section class="cs-section">
        <div class="cs-section-heading">
            <h2>Alur pendaftaran<br><span class="cs-accent">sederhana.</span></h2>
            <p>Pantau setiap tahap seleksi kapan saja lewat Portal Peserta menggunakan email pendaftaranmu.</p>
        </div>

        <div class="cs-steps">
            <div class="cs-step">
                <span>01</span>
                <h3>Pendaftaran</h3>
                <p>Isi formulir dan unggah berkas persyaratan dalam format PDF.</p>
            </div>
            <div class="cs-step">
                <span>02</span>
                <h3>Seleksi Berkas</h3>
                <p>Tim HR memeriksa kelengkapan dan kesesuaian berkasmu.</p>
            </div>
            <div class="cs-step">
                <span>03</span>
                <h3>Wawancara User</h3>
                <p>Peserta yang lolos berkas menerima jadwal wawancara.</p>
            </div>
            <div class="cs-step">
                <span>04</span>
                <h3>Pengumuman Final</h3>
                <p>Hasil seleksi dapat dilihat langsung di Portal Peserta.</p>
            </div>
        </div>
    </section>

    {{-- PORTAL PESERTA --}}
    <section id="portal" style="padding-bottom:80px;">
        <div class="cs-portal-band">
            <div>
                <div class="cs-eyebrow">Portal Peserta</div>
                <h2>Pantau pendaftaranmu,<br><span class="cs-accent">kapan saja.</span></h2>
                <p>Masukkan email yang kamu gunakan saat mendaftar untuk melihat data pendaftaran dan progres seleksimu.</p>
                <a href="{{ route('magang.cek-status') }}" class="cs-btn">Buka Portal Peserta <span>→</span></a>
            </div>
            <div class="cs-portal-points">
                <div class="cs-portal-point"><span>👤</span>
                    <div><strong>Data pendaftar</strong><small>Ringkasan data diri, asal kampus, jurusan, dan periode magang.</small></div>
                </div>
                <div class="cs-portal-point"><span>📍</span>
                    <div><strong>Status terkini</strong><small>Menunggu review, wawancara, diterima, atau tidak lolos.</small></div>
                </div>
                <div class="cs-portal-point"><span>🗓️</span>
                    <div><strong>Progres &amp; jadwal</strong><small>Timeline seleksi, undangan wawancara, dan pesan dari HR.</small></div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section>
        <div class="cs-cta-band">
            <div>
                <div class="cs-eyebrow">Siap bergabung?</div>
                <h2>Mulai langkah pertamamu<br>bersama <i style="font-family:Georgia,serif;font-weight:500;">PLN Suku Cadang.</i></h2>
            </div>
            <a href="{{ route('magang.form') }}" class="cs-btn cs-btn-dark">Daftar Sekarang <span>→</span></a>
            <span class="cs-cta-star" aria-hidden="true">✳</span>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="cs-section cs-faq" id="faq" style="padding-top:20px;">
        <div>
            <div class="cs-eyebrow">Masih punya pertanyaan?</div>
            <h2>FAQ, biar <span class="cs-accent">jelas.</span></h2>
            <p class="cs-faq-contact">Pertanyaan lain? Hubungi kami di
                <a href="mailto:lukman.zuhdi@plnsc.co.id">lukman.zuhdi@plnsc.co.id</a>
                atau nomor HP <a href="tel:+62 815-7879-1145">+62 815-7879-1145</a>.
            </p>
        </div>
        <div class="cs-faq-list">
            <details>
                <summary>Siapa yang boleh mendaftar?<span>＋</span></summary>
                <p>Fresh graduate dan mahasiswa aktif. Mahasiswa aktif dapat mendaftar untuk kegiatan PKL / magang
                    wajib atau riset / penelitian. Pilih jenis magang yang sesuai pada formulir pendaftaran.</p>
            </details>
            <details>
                <summary>Berkas apa saja yang perlu disiapkan?<span>＋</span></summary>
                <p>Surat permohonan magang, Curriculum Vitae (CV), dan transkrip nilai terakhir. Mahasiswa aktif juga
                    perlu melampirkan surat pengantar kampus. Dokumen pendukung seperti sertifikat atau portofolio
                    bersifat opsional.</p>
                <p>Semua berkas diunggah dalam format PDF dengan ukuran maksimal 2 MB per berkas utama.</p>
            </details>
            <details>
                <summary>Bagaimana cara memantau status pendaftaran?<span>＋</span></summary>
                <p>Buka menu Portal Peserta, lalu masukkan email yang kamu gunakan saat mendaftar. Kamu dapat melihat
                    data pendaftaran serta progres seleksi: pendaftaran, seleksi berkas, wawancara, dan pengumuman
                    final.</p>
            </details>
            <details>
                <summary>Bisakah satu email dipakai untuk lebih dari satu pendaftaran?<span>＋</span></summary>
                <p>Tidak. Satu alamat email hanya dapat digunakan untuk satu pendaftaran, jadi pastikan email yang
                    kamu isi aktif dan benar.</p>
            </details>
            <details>
                <summary>Ke mana saya bisa bertanya lebih lanjut?<span>＋</span></summary>
                <p>Silakan kirim email ke <a href="mailto:lukman.zuhdi@plnsc.co.id"
                        style="color:var(--cs-primary);font-weight:700;">lukman.zuhdi@plnsc.co.id</a>
                    atau hubungi nomor HP <a href="tel:+62 815-7879-1145"
                        style="color:var(--cs-primary);font-weight:700;">+62 815-7879-1145</a>.</p>
            </details>
        </div>
    </section>

    {{-- MODAL CEK STATUS --}}
    <div id="statusModal" class="cs-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title"
        onclick="if (event.target === this) closeModal()">
        <div class="cs-modal-card">
            <div class="cs-modal-head">
                <h3 id="modal-title">Cek Status Lamaran</h3>
                <p>Masukkan alamat email yang Anda gunakan saat mendaftar.</p>
                <button type="button" onclick="closeModal()" class="cs-modal-close" aria-label="Tutup">✕</button>
            </div>
            <div class="cs-modal-body">
                <form action="{{ route('magang.cek-status.result') }}" method="POST">
                    @csrf
                    <label for="email">Alamat Email</label>
                    <input type="email" name="email" id="email" required placeholder="contoh@email.com">
                    <button type="submit" class="cs-btn">Lacak Lamaran Saya</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('statusModal').classList.add('is-open');
            document.body.style.overflow = 'hidden'; // Mencegah scroll di body saat modal terbuka
        }

        function closeModal() {
            document.getElementById('statusModal').classList.remove('is-open');
            document.body.style.overflow = 'auto'; // Mengaktifkan kembali scroll
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
@endsection
