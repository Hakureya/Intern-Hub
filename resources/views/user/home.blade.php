@extends('layouts.master-blank')

@section('content')
@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=2" rel="stylesheet" type="text/css" />
@endsection

@php
    $selectedCategory = $selectedCategory ?? 'all';
    $categoryLabel = function ($key) {
        $map = [
            'all' => 'Semua',
            'compro' => 'Compro',
            'laporan_keuangan' => 'Laporan Keuangan',
            'pedoman_sop' => 'Pedoman / SOP',
            'materi_orientasi' => 'Materi Orientasi',
            'lainnya' => 'Lainnya',
        ];
        return $map[$key] ?? 'Lainnya';
    };
@endphp

@include('layouts.user-navbar', ['active' => 'home'])

<div class="home-wrap">
    <div class="home-shell">
        <div class="hero-card hero-card--batik">
            <h1 class="hero-title">Home</h1>
            <p class="hero-subtitle">Perpustakaan dokumen magang dari admin untuk panduan peserta.</p>
        </div>

        <div class="doc-card">
            <div class="doc-head">
                <div>
                    <h2 class="doc-title">Dokumen Magang &amp; FAQ</h2>
                    <div class="doc-subtitle">Pilih kategori lalu buka dokumen dengan tombol Lihat.</div>
                </div>
            </div>
            @if ($documents->isEmpty())
                <div class="empty-box">Belum ada dokumen dossier pada kategori ini.</div>
            @else
                <div class="doc-grid">
                    @foreach ($documents as $item)
                        <div class="doc-item dossier-spotlight">
                            <div class="doc-item-content">
                                <span class="doc-item-icon"><i class="mdi mdi-file-document-outline"></i></span>
                                <span
                                    class="doc-item-category">{{ $categoryLabel($item->library_category ?: 'lainnya') }}</span>
                                <div class="doc-item-title">{{ $item->title }}</div>
                                <div class="doc-item-desc">
                                    {{ \Illuminate\Support\Str::limit($item->description ?: 'Tidak ada deskripsi.', 100) }}
                                </div>
                                <div class="doc-item-file">{{ $item->file_name }}</div>
                                <div class="doc-actions">
                                    <button type="button" class="btn-live btn-template-preview"
                                        data-title="{{ $item->title }}"
                                        data-preview-url="{{ route('user.template.view', $item->id) }}">
                                        Lihat
                                    </button>
                                    <a class="btn-download"
                                        href="{{ route('user.template.download', $item->id) }}">Unduh</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <section class="quick-menu-section">
            <div class="quick-menu-head">
                <h2>Menu</h2>
                <p>Akses cepat fitur inti untuk aktivitas magang harian.</p>
            </div>
            <div class="quick-menu-grid">
                <a href="{{ route('user.attendance.index') }}" class="quick-menu-card c2">
                    <span class="quick-menu-icon"><i class="mdi mdi-clock-outline"></i></span>
                    <h3 class="quick-menu-title">Kehadiran</h3>
                    <p class="quick-menu-desc">Presensi masuk dan pulang sesuai jadwal kerja.</p>
                    <span class="quick-menu-link">Buka Menu -></span>
                </a>

                <a href="{{ route('user.attendance.history') }}" class="quick-menu-card c3">
                    <span class="quick-menu-icon"><i class="mdi mdi-clipboard-text-outline"></i></span>
                    <h3 class="quick-menu-title">Riwayat Kehadiran</h3>
                    <p class="quick-menu-desc">Pantau kalender presensi dan draft histori kehadiran.</p>
                    <span class="quick-menu-link">Buka Menu -></span>
                </a>

                <a href="{{ route('user.absence.index', ['type' => 'izin_sakit']) }}" class="quick-menu-card c4">
                    <span class="quick-menu-icon"><i class="mdi mdi-clipboard-text-outline"></i></span>
                    <h3 class="quick-menu-title">Ketidakhadiran</h3>
                    <p class="quick-menu-desc">Ajukan izin, sakit, atau laporan lupa absensi.</p>
                    <span class="quick-menu-link">Buka Menu -></span>
                </a>

                <a href="{{ route('user.monthly-report') }}" class="quick-menu-card c5">
                    <span class="quick-menu-icon"><i class="mdi mdi-file-document-outline"></i></span>
                    <h3 class="quick-menu-title">Laporan Magang</h3>
                    <p class="quick-menu-desc">Kelola laporan bulanan serta laporan akhir magang.</p>
                    <span class="quick-menu-link">Buka Menu -></span>
                </a>

                <a href="{{ route('user.profile') }}" class="quick-menu-card c6">
                    <span class="quick-menu-icon"><i class="mdi mdi-account-outline"></i></span>
                    <h3 class="quick-menu-title">Profil</h3>
                    <p class="quick-menu-desc">Perbarui data pribadi dan informasi profil peserta.</p>
                    <span class="quick-menu-link">Buka Menu -></span>
                </a>
            </div>
        </section>
    </div>
</div>

<div class="template-preview-modal" id="template-preview-modal" aria-hidden="true">
    <div class="template-preview-card">
        <div class="template-preview-head">
            <div class="template-preview-title" id="template-preview-title">Preview Dokumen</div>
            <button type="button" class="btn btn-sm btn-danger" id="template-preview-close">Tutup</button>
        </div>
        <iframe id="template-preview-frame" class="template-preview-frame" src=""></iframe>
    </div>
</div>
@endsection

@section('script')
<script>
    (function() {
        var modal = document.getElementById('template-preview-modal');
        var frame = document.getElementById('template-preview-frame');
        var title = document.getElementById('template-preview-title');
        var closeBtn = document.getElementById('template-preview-close');

        if (!modal || !frame || !title || !closeBtn) {
            return;
        }

        function closePreview() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            frame.setAttribute('src', '');
        }

        document.querySelectorAll('.btn-template-preview').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var url = btn.getAttribute('data-preview-url') || '';
                title.textContent = btn.getAttribute('data-title') || 'Preview Dokumen';
                frame.setAttribute('src', url + '#toolbar=0&navpanes=0&scrollbar=0');
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        closeBtn.addEventListener('click', closePreview);
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closePreview();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePreview();
            }
        });

        document.querySelectorAll('.dossier-spotlight').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                card.style.setProperty('--spot-opacity', '1');
            });

            card.addEventListener('mouseleave', function() {
                card.style.setProperty('--spot-opacity', '0');
            });

            card.addEventListener('mousemove', function(event) {
                var rect = card.getBoundingClientRect();
                var x = event.clientX - rect.left;
                var y = event.clientY - rect.top;
                card.style.setProperty('--mx', x + 'px');
                card.style.setProperty('--my', y + 'px');
            });
        });
    })();
</script>
@endsection
