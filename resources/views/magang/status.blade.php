@extends('magang.layout.app')

@section('title', 'Portal Peserta - PLN Suku Cadang')

@section('content')
    @php
        $hasData = isset($pendaftar);

        // Samarkan data sensitif: halaman ini dibuka hanya dengan email.
        $maskEmail = function ($email) {
            $parts = explode('@', (string) $email, 2);
            if (count($parts) !== 2) {
                return '-';
            }
            $local = $parts[0];
            $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
            return $visible . str_repeat('*', max(mb_strlen($local) - 2, 3)) . '@' . $parts[1];
        };
        $maskPhone = function ($phone) {
            $phone = preg_replace('/\D+/', '', (string) $phone);
            if (strlen($phone) < 8) {
                return '-';
            }
            return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
        };

        if ($hasData) {
            $namaPelamar = $pendaftar->nama;
            $statusDB = $pendaftar->status;
            $tglDaftar = $pendaftar->created_at->format('d M Y');

            $initials = '';
            foreach (array_slice(array_filter(preg_split('/\s+/', trim($namaPelamar))), 0, 2) as $w) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
            }

            $jenisLabel = $pendaftar->jenis_magang === 'mahasiswa' ? 'Mahasiswa Aktif' : 'Fresh Graduate';
            $tipeLabel = null;
            if ($pendaftar->jenis_magang === 'mahasiswa') {
                $tipeLabel = $pendaftar->tipe_mahasiswa === 'riset' ? 'Riset / Penelitian' : 'PKL / Magang Wajib';
            }

            // Timeline dinamis
            $s1 = 'done';
            $s2 = 'current';
            $s3 = 'upcoming';
            $s4 = 'upcoming';
            if ($statusDB == 'Wawancara') {
                $s2 = 'done';
                $s3 = 'current';
            } elseif ($statusDB == 'Diterima') {
                $s2 = $s3 = $s4 = 'done';
            } elseif ($statusDB == 'Ditolak') {
                $s2 = 'done';
                $s3 = $s4 = 'upcoming';
            }
            $timeline = [
                ['step' => 'Pendaftaran', 'status' => $s1, 'date' => $tglDaftar],
                ['step' => 'Seleksi Berkas', 'status' => $s2, 'date' => $statusDB == 'Menunggu' ? 'Sedang berjalan' : 'Selesai'],
                ['step' => 'Wawancara User', 'status' => $s3, 'date' => $statusDB == 'Wawancara' ? 'Dijadwalkan' : '-'],
                ['step' => 'Pengumuman Final', 'status' => $s4, 'date' => $statusDB == 'Diterima' ? 'Diterima' : '-'],
            ];

            // Progres seleksi: 3 tahap -> 33% (seleksi berkas), 67% (wawancara), 100% (diterima).
            // Ditolak berhenti di 67%, sama seperti tahap wawancara.
            $progress = [
                'Menunggu' => 33,
                'Wawancara' => 67,
                'Ditolak' => 67,
                'Diterima' => 100,
            ][$statusDB] ?? 33;

            // Perjalanan magang (5 tahap) beserta penjelasan tiap tahap
            $journey = [
                ['step' => 'Pendaftaran', 'status' => $s1, 'desc' => 'Formulir dan berkas persyaratan berhasil dikirim.', 'date' => $tglDaftar],
                ['step' => 'Seleksi Berkas', 'status' => $s2, 'desc' => 'Tim HR memeriksa kelengkapan dan kesesuaian berkasmu.', 'date' => $statusDB == 'Menunggu' ? 'Sedang berjalan' : 'Selesai'],
                ['step' => 'Wawancara User', 'status' => $s3, 'desc' => 'Wawancara sesuai jadwal yang diberikan untuk peserta yang lolos berkas.', 'date' => $statusDB == 'Wawancara' ? 'Dijadwalkan' : '-'],
                ['step' => 'Pengumuman Final', 'status' => $s4, 'desc' => 'Hasil akhir seleksi diumumkan di Portal Peserta ini.', 'date' => $statusDB == 'Diterima' ? 'Diterima' : '-'],
                ['step' => 'Mulai Magang', 'status' => $statusDB == 'Diterima' ? 'current' : 'upcoming', 'desc' => 'Informasi lanjutan sebelum magang dimulai disampaikan oleh HR.', 'date' => $statusDB == 'Diterima' ? 'Menunggu info HR' : '-'],
            ];

            // Langkah berikutnya untuk peserta
            $nextStep = 'Tunggu hasil seleksi berkas dari tim HR.';
            if ($statusDB == 'Wawancara') {
                $nextStep = 'Hadiri wawancara sesuai jadwal yang tertera.';
            } elseif ($statusDB == 'Diterima') {
                $nextStep = 'Pantau email untuk informasi lanjutan dari HR.';
            } elseif ($statusDB == 'Ditolak') {
                $nextStep = 'Tidak ada langkah lanjutan untuk periode ini.';
            }

            // Label & warna status
            $statusLabel = 'Menunggu Review';
            $statusClass = 'is-warn';
            $statusDesc = 'Berkasmu sedang diperiksa oleh tim HR. Kami akan memperbarui status di halaman ini.';
            if ($statusDB == 'Wawancara') {
                $statusLabel = 'Tahap Wawancara';
                $statusClass = '';
                $statusDesc = 'Selamat, kamu lolos tahap berkas. Silakan cek jadwal wawancara di bawah.';
            } elseif ($statusDB == 'Diterima') {
                $statusLabel = 'Diterima';
                $statusClass = 'is-good';
                $statusDesc = 'Selamat bergabung! Cek email kamu untuk informasi selanjutnya.';
            } elseif ($statusDB == 'Ditolak') {
                $statusLabel = 'Tidak Lolos';
                $statusClass = 'is-bad';
                $statusDesc = 'Mohon maaf, kamu belum lolos tahap ini. Tetap semangat, masih banyak kesempatan lain.';
            }

            $berkas = ['CV', 'Transkrip nilai', 'Surat permohonan'];
            if (!empty($pendaftar->surat_path)) {
                $berkas[] = 'Surat pengantar kampus';
            }
            $jumlahPendukung = is_array($pendaftar->dokumen_pendukung_path) ? count($pendaftar->dokumen_pendukung_path) : 0;
        }
    @endphp

    @if (!$hasData)
        {{-- Setelah mengirim formulir, atau membuka halaman tanpa data --}}
        <main class="cs-dash">
            <div class="cs-card cs-dash-empty animate-fade-in-up" style="padding:40px 30px;">
                <div class="cs-empty-icon">✓</div>
                <div class="cs-eyebrow" style="margin-bottom:0;">Portal Peserta</div>
                <h1>{{ session('success') ? 'Pendaftaran diterima.' : 'Lacak pendaftaranmu.' }}</h1>
                <p>
                    {{ session('success') ?? 'Masukkan email yang kamu gunakan saat mendaftar untuk melihat data dan progres seleksimu.' }}
                    @if (session('success'))
                        Gunakan email pendaftaranmu di Portal Peserta untuk memantau progres seleksi.
                    @endif
                </p>
                <a href="{{ route('magang.cek-status') }}" class="cs-btn">Buka Portal Peserta <span>→</span></a>
                <div><a href="{{ route('welcome') }}" class="cs-back">← Kembali ke beranda</a></div>
            </div>
        </main>
    @else
        <main class="cs-dash">
            <div class="cs-dash-head animate-fade-in-up">
                <div>
                    <div class="cs-eyebrow">Portal Peserta</div>
                    <h1>Halo, <span class="cs-accent">{{ $namaPelamar }}!</span></h1>
                    <p>Berikut data pendaftaran dan progres seleksimu.</p>
                </div>
                <div class="cs-dash-avatar">{{ $initials ?: 'P' }}</div>
            </div>

            {{-- Pesan HR (Diterima) --}}
            @if ($pendaftar->status == 'Diterima' && $pendaftar->pesan)
                <div class="cs-notice is-success animate-fade-in-up">
                    <h3>Pesan dari HRD</h3>
                    <p>“{{ $pendaftar->pesan }}”</p>
                </div>
            @endif

            {{-- Undangan wawancara --}}
            @if ($pendaftar->status == 'Wawancara' && $pendaftar->wawancara_waktu)
                <div class="cs-notice is-info animate-fade-in-up">
                    <h3>Undangan Wawancara</h3>
                    <p>Selamat! Kamu lolos tahap berkas. Silakan hadiri wawancara berikut:</p>
                    <div class="cs-notice-box">
                        <div>
                            <small>Waktu</small>
                            <strong>{{ $pendaftar->wawancara_waktu->translatedFormat('l, d F Y') }}</strong>
                            <div style="font-size:12px;color:#6d808e;margin-top:3px;">Pukul
                                {{ $pendaftar->wawancara_waktu->format('H:i') }} WIB</div>
                        </div>
                        <div>
                            <small>Lokasi / Link</small>
                            <strong>{{ $pendaftar->wawancara_lokasi }}</strong>
                        </div>
                    </div>
                    @if ($pendaftar->pesan)
                        <p style="margin-top:14px;"><strong>Pesan admin:</strong> “{{ $pendaftar->pesan }}”</p>
                    @endif
                </div>
            @endif

            {{-- Navigasi tab --}}
            <div class="cs-tabs" role="tablist" aria-label="Menu Portal Peserta">
                <button type="button" class="cs-tab is-active" role="tab" data-tab="ringkasan"
                    aria-selected="true">Ringkasan</button>
                <button type="button" class="cs-tab" role="tab" data-tab="aplikasi" aria-selected="false">Aplikasi
                    Saya</button>
                <button type="button" class="cs-tab" role="tab" data-tab="perjalanan" aria-selected="false">Perjalanan
                    Magang</button>
            </div>

            {{-- TAB: RINGKASAN --}}
            <section class="cs-tab-panel is-active" id="tab-ringkasan" role="tabpanel">
                <div class="cs-dash-grid">
                    <div>
                        <div class="cs-card is-sky animate-fade-in-up">
                            <span class="cs-status-pill {{ $statusClass }}">STATUS TERKINI</span>
                            <h2>{{ $statusLabel }}</h2>
                            <p class="cs-card-lead">{{ $statusDesc }}</p>

                            <div class="cs-progress-label">
                                <span>Progres seleksi</span><strong>{{ $progress }}%</strong>
                            </div>
                            <div class="cs-progress"><i style="width: {{ $progress }}%;"></i></div>
                        </div>

                        <div class="cs-mini-stats animate-fade-in-up">
                            <div class="cs-mini-stat">
                                <small>Tanggal daftar</small>
                                <strong>{{ $tglDaftar }}</strong>
                            </div>
                            <div class="cs-mini-stat">
                                <small>Jenis magang</small>
                                <strong>{{ $jenisLabel }}</strong>
                            </div>
                            <div class="cs-mini-stat">
                                <small>Rencana periode</small>
                                <strong>{{ $pendaftar->periode ?: '-' }}</strong>
                            </div>
                        </div>

                        <div class="cs-card animate-fade-in-up">
                            <h3>Langkah berikutnya</h3>
                            <p class="cs-card-lead" style="margin-top:10px;color:#55646e;">{{ $nextStep }}</p>
                            <button type="button" class="cs-btn cs-btn-outline" data-tab-link="perjalanan"
                                style="margin-top:16px;">Lihat perjalanan magang →</button>
                        </div>
                    </div>

                    <div>
                        <div class="cs-card animate-fade-in-up">
                            <h3>Ringkasan pendaftar</h3>
                            <div class="cs-line"><span>Nama</span><strong>{{ $pendaftar->nama }}</strong></div>
                            <div class="cs-line"><span>Institusi</span><strong>{{ $pendaftar->asal_kampus }}</strong></div>
                            <div class="cs-line"><span>Jurusan</span><strong>{{ $pendaftar->jurusan }}</strong></div>
                            <button type="button" class="cs-btn cs-btn-outline" data-tab-link="aplikasi"
                                style="margin-top:16px;width:100%;">Lihat aplikasi saya</button>
                        </div>

                        <div class="cs-card animate-fade-in-up" style="background:#e8f4f8;border-color:#e8f4f8;">
                            <h3>Butuh bantuan?</h3>
                            <p style="font-size:12px;line-height:1.8;color:#73766b;margin:10px 0 0;">Hubungi kami di
                                <a href="mailto:lukman.zuhdi@plnsc.co.id"
                                    style="color:var(--cs-primary);font-weight:700;">lukman.zuhdi@plnsc.co.id</a>
                                atau nomor HP
                                <a href="tel:+62 815-7879-1145"
                                    style="color:var(--cs-primary);font-weight:700;">+62 815-7879-1145</a>.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- TAB: APLIKASI SAYA --}}
            <section class="cs-tab-panel" id="tab-aplikasi" role="tabpanel">
                <div class="cs-dash-grid">
                    <div class="cs-card animate-fade-in-up">
                        <h3>Detail pendaftaran</h3>
                        <div class="cs-line"><span>Nama lengkap</span><strong>{{ $pendaftar->nama }}</strong></div>
                        <div class="cs-line"><span>Jenis magang</span><strong>{{ $jenisLabel }}</strong></div>
                        @if ($tipeLabel)
                            <div class="cs-line"><span>Kategori kegiatan</span><strong>{{ $tipeLabel }}</strong></div>
                        @endif
                        <div class="cs-line"><span>Asal kampus / institusi</span><strong>{{ $pendaftar->asal_kampus }}</strong></div>
                        <div class="cs-line"><span>Jurusan / prodi</span><strong>{{ $pendaftar->jurusan }}</strong></div>
                        @if ($pendaftar->jenis_magang === 'mahasiswa' && $pendaftar->semester)
                            <div class="cs-line"><span>Semester</span><strong>{{ $pendaftar->semester }}</strong></div>
                        @endif
                        <div class="cs-line"><span>Rencana periode magang</span><strong>{{ $pendaftar->periode ?: '-' }}</strong></div>
                        <div class="cs-line"><span>Tanggal pendaftaran</span><strong>{{ $tglDaftar }}</strong></div>
                        <div class="cs-line"><span>Status</span><strong>{{ $statusLabel }}</strong></div>
                    </div>

                    <div>
                        <div class="cs-card animate-fade-in-up">
                            <h3>Kontak terdaftar</h3>
                            <div class="cs-line"><span>Email</span><strong>{{ $maskEmail($pendaftar->email) }}</strong></div>
                            <div class="cs-line"><span>WhatsApp</span><strong>{{ $maskPhone($pendaftar->no_hp) }}</strong></div>
                            <p style="font-size:11px;color:#8c9ca7;line-height:1.7;margin:14px 0 0;">Sebagian data
                                disamarkan demi keamanan.</p>
                        </div>

                        <div class="cs-card animate-fade-in-up">
                            <h3>Berkas terunggah</h3>
                            <div class="cs-files">
                                @foreach ($berkas as $nama)
                                    <span>✓ {{ $nama }}</span>
                                @endforeach
                                @if ($jumlahPendukung > 0)
                                    <span>✓ Dokumen pendukung ({{ $jumlahPendukung }})</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- TAB: PERJALANAN MAGANG --}}
            <section class="cs-tab-panel" id="tab-perjalanan" role="tabpanel">
                <div class="cs-card is-sky animate-fade-in-up" style="max-width:760px;">
                    <span class="cs-status-pill {{ $statusClass }}">{{ strtoupper($statusLabel) }}</span>
                    <h2>Perjalanan magangmu</h2>
                    <p class="cs-card-lead">Dari pendaftaran hingga hari pertama magang.</p>

                    <div class="cs-journey">
                        @foreach ($journey as $index => $item)
                            <div class="cs-journey-step is-{{ $item['status'] }}">
                                <span>{{ $item['status'] == 'done' ? '✓' : $index + 1 }}</span>
                                <div>
                                    <strong>{{ $item['step'] }}</strong>
                                    <small>{{ $item['desc'] }}</small>
                                </div>
                                <em>{{ $item['date'] }}</em>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <noscript>
                <style>
                    .cs-tab-panel {
                        display: block !important;
                        margin-bottom: 28px;
                    }

                    .cs-tabs {
                        display: none;
                    }
                </style>
            </noscript>

            <script>
                (function() {
                    var tabs = document.querySelectorAll('.cs-tab');
                    var panels = document.querySelectorAll('.cs-tab-panel');
                    var names = ['ringkasan', 'aplikasi', 'perjalanan'];

                    function show(name, updateHash) {
                        if (names.indexOf(name) === -1) name = 'ringkasan';
                        tabs.forEach(function(t) {
                            var on = t.getAttribute('data-tab') === name;
                            t.classList.toggle('is-active', on);
                            t.setAttribute('aria-selected', on ? 'true' : 'false');
                        });
                        panels.forEach(function(p) {
                            p.classList.toggle('is-active', p.id === 'tab-' + name);
                        });
                        if (updateHash && history.replaceState) {
                            history.replaceState(null, '', '#' + name);
                        }
                    }
                    tabs.forEach(function(t) {
                        t.addEventListener('click', function() {
                            show(t.getAttribute('data-tab'), true);
                        });
                    });
                    document.querySelectorAll('[data-tab-link]').forEach(function(b) {
                        b.addEventListener('click', function() {
                            show(b.getAttribute('data-tab-link'), true);
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                    });
                    show((location.hash || '').replace('#', ''), false);
                })();
            </script>

            <div style="text-align:center;">
                <a href="{{ route('welcome') }}" class="cs-back">← Kembali ke beranda</a>
            </div>
        </main>
    @endif
@endsection
