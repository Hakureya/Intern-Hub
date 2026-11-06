@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=1" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/laporan-bulanan.css') }}?v=6" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php
    if (!function_exists('format_filesize')) {
        function format_filesize($bytes)
        {
            $bytes = (int) $bytes;
            if ($bytes <= 0) {
                return '-';
            }
            $units = ['B', 'KB', 'MB', 'GB'];
            $i = 0;
            while ($bytes >= 1024 && $i < count($units) - 1) {
                $bytes /= 1024;
                $i++;
            }
            return number_format($bytes, 2) . ' ' . $units[$i];
        }
    }

    // Periode laporan: bulan & tahun saja (dikirim sebagai YYYY-MM, sesuai validasi controller)
    $currentPeriod = \Carbon\Carbon::now()->format('Y-m');
    $selectedPeriod = old('report_month', $currentPeriod);
@endphp
@include('layouts.user-navbar', ['active' => 'monthly'])

<div class="mr-page">
    <div class="mr-shell">
        <p class="mr-eyebrow">Pelaporan berkala</p>
        <h1 class="mr-title">Laporan <em>bulanan.</em></h1>
        <p class="mr-lead">Unduh template, isi kegiatan dan hasil belajarmu, lalu unggah laporan untuk ditinjau mentor.</p>

        <div class="mr-grid">
            {{-- Kartu template --}}
            <section class="mr-card" aria-labelledby="mr-template-title">
                <div class="mr-banner mr-banner--monthly" aria-hidden="true">
                    <p class="mr-banner-text">MONTHLY<br>REPORT</p>
                </div>
                <div class="mr-template-body">
                    <span class="mr-badge">Template resmi</span>
                    <h2 class="mr-template-title" id="mr-template-title">{{ optional($latestTemplate)->title ?: 'Template Laporan Magang Bulanan' }}</h2>
                    @php
                        $descParts = $latestTemplate ? $latestTemplate->description_parts : ['intro' => '', 'items' => []];
                    @endphp
                    @if ($descParts['intro'])
                        <p class="mr-template-desc">{{ $descParts['intro'] }}</p>
                    @endif
                    @if (count($descParts['items']))
                        <ul class="mr-checklist">
                            @foreach ($descParts['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mr-actions">
                        @if ($latestTemplate)
                            <a class="mr-btn" href="{{ route('user.template.download', $latestTemplate->id) }}">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M8 2v8M4.5 7 8 10.5 11.5 7M3 13.5h10" />
                                </svg>
                                Unduh template
                            </a>
                            <button type="button" class="mr-btn mr-btn-ghost btn-template-preview"
                                data-title="{{ $latestTemplate->title }}"
                                data-name="{{ $latestTemplate->file_name }}"
                                data-preview-url="{{ route('user.template.view', $latestTemplate->id) }}"
                                data-download-url="{{ route('user.template.download', $latestTemplate->id) }}"
                                data-mime="{{ $latestTemplate->mime_type ?? '' }}">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1.5 8S4 3.5 8 3.5 14.5 8 14.5 8 12 12.5 8 12.5 1.5 8 1.5 8Z" />
                                    <circle cx="8" cy="8" r="2" />
                                </svg>
                                Lihat
                            </button>
                        @else
                            <button type="button" class="mr-btn" disabled>Template belum tersedia</button>
                        @endif
                    </div>

                    @if (($templateDocuments ?? collect())->count() > 1)
                        <div class="mr-extra">
                            <p class="mr-extra-label">Template lainnya</p>
                            @foreach ($templateDocuments->skip(1) as $doc)
                                <div class="mr-extra-item">
                                    <span class="mr-extra-name">{{ $doc->title }}</span>
                                    <span class="mr-extra-actions">
                                        <button type="button" class="mr-link-btn btn-template-preview"
                                            data-title="{{ $doc->title }}"
                                            data-name="{{ $doc->file_name }}"
                                            data-download-url="{{ route('user.template.download', $doc->id) }}"
                                            data-preview-url="{{ route('user.template.view', $doc->id) }}"
                                            data-mime="{{ $doc->mime_type ?? '' }}">Lihat</button>
                                        <a class="mr-link-btn"
                                            href="{{ route('user.template.download', $doc->id) }}">Unduh</a>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Form unggah --}}
            <section class="mr-card mr-upload" aria-labelledby="mr-upload-title">
                <div class="mr-upload-head">
                    <div>
                        <h2 class="mr-upload-title" id="mr-upload-title">Unggah laporan bulanan</h2>
                        <p class="mr-upload-sub">Gunakan file yang sudah kamu lengkapi.</p>
                    </div>
                    <span class="mr-required">Wajib</span>
                </div>

                @if ($errors->any())
                    <ul class="mr-errors" role="alert">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('monthly-report.store') }}" class="mr-form"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="mr-field">
                        <label class="mr-label" for="report_month">Periode laporan <i>*</i></label>
                        <input type="month" class="mr-input" id="report_month" name="report_month"
                            value="{{ $selectedPeriod }}" max="{{ $currentPeriod }}" placeholder="YYYY-MM"
                            pattern="\d{4}-\d{2}" required>
                        <p class="mr-hint">Pilih bulan dan tahun laporan.</p>
                    </div>

                    <div class="mr-field">
                        <label class="mr-label" for="monthly_report">File laporan <i>*</i></label>
                        <div class="mr-file">
                            <input type="file" id="monthly_report" name="monthly_report" accept=".pdf,application/pdf"
                                required>
                        </div>
                        <p class="mr-hint">Format PDF, maksimal 5 MB.</p>
                    </div>

                    <button class="mr-btn mr-btn-lg" type="submit">
                        Unggah laporan
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 11 11 5M6 5h5v5" />
                        </svg>
                    </button>
                </form>
            </section>

            {{-- Riwayat (kartu terpisah) --}}
            <section class="mr-card mr-history-card" aria-labelledby="mr-history-title">
                <div class="mr-history">
                    <h3 class="mr-history-title" id="mr-history-title">Riwayat laporan bulanan</h3>
                    @if ($reports->isEmpty())
                        <p class="mr-empty">Belum ada berkas yang diunggah.</p>
                    @else
                        <ul class="mr-list">
                            @foreach ($reports as $report)
                                @php
                                    try {
                                        $periodLabel = \Carbon\Carbon::createFromFormat('Y-m', $report->report_month)
                                            ->locale('id')
                                            ->translatedFormat('F Y');
                                    } catch (\Throwable $e) {
                                        $periodLabel = $report->report_month;
                                    }
                                @endphp
                                <li class="mr-row">
                                    <div class="mr-row-main">
                                        <p class="mr-row-month">{{ $periodLabel }}</p>
                                        <p class="mr-row-meta">
                                            {{ optional($report->created_at)->format('d-m-Y H:i') }}
                                            &middot; {{ format_filesize($report->file_size) }}
                                            &middot; {{ $report->file_name }}
                                        </p>
                                    </div>
                                    <div class="mr-row-actions">
                                        <a class="mr-link-btn" target="_blank" rel="noopener"
                                            href="{{ route('monthly-report.view', $report->id) }}">Lihat</a>
                                        <a class="mr-link-btn"
                                            href="{{ route('monthly-report.download', $report->id) }}">Unduh</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

<div class="template-preview-modal" id="template-preview-modal" aria-hidden="true">
    <div class="template-preview-card">
        <div class="template-preview-head">
            <div class="template-preview-title" id="template-preview-title">Preview Dokumen</div>
            <button type="button" class="btn btn-sm btn-danger" id="template-preview-close">Tutup</button>
        </div>
        <div class="template-preview-body" id="template-preview-body">
            <div class="template-preview-loading" id="template-preview-loading">Memuat dokumen...</div>
            <div class="template-pdf-pages" id="template-pdf-pages"></div>
            <div class="mr-docx" id="template-docx-body"></div>
            <iframe id="template-preview-frame" class="template-preview-frame" src=""></iframe>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.5.136/build/pdf.min.mjs" type="module"></script>
<script>
    (async function() {
        var modal = document.getElementById('template-preview-modal');
        var frame = document.getElementById('template-preview-frame');
        var title = document.getElementById('template-preview-title');
        var closeBtn = document.getElementById('template-preview-close');
        var loading = document.getElementById('template-preview-loading');
        var pages = document.getElementById('template-pdf-pages');
        var docxBox = document.getElementById('template-docx-body');

        if (!modal || !frame || !title || !closeBtn || !loading || !pages) {
            return;
        }

        var pdfjsLib = null;
        try {
            pdfjsLib = await import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.5.136/build/pdf.min.mjs');
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.5.136/build/pdf.worker.min.mjs';
        } catch (e) {
            pdfjsLib = null;
        }

        function loadScript(src) {
            return new Promise(function(resolve, reject) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        var docxReady = null;

        function ensureDocxLib() {
            if (window.docx) {
                return Promise.resolve();
            }
            if (!docxReady) {
                docxReady = loadScript('https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js')
                    .then(function() {
                        return loadScript(
                            'https://cdn.jsdelivr.net/npm/docx-preview@0.3.3/dist/docx-preview.min.js');
                    });
            }
            return docxReady;
        }

        function closePreview() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            frame.setAttribute('src', '');
            frame.style.display = 'none';
            pages.innerHTML = '';
            if (docxBox) {
                docxBox.innerHTML = '';
                docxBox.style.display = 'none';
            }
            loading.textContent = 'Memuat dokumen...';
            loading.style.display = 'block';
        }

        document.querySelectorAll('.btn-template-preview').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                var url = btn.getAttribute('data-preview-url') || '';
                var mime = (btn.getAttribute('data-mime') || '').toLowerCase();
                var fileName = (btn.getAttribute('data-name') || '').toLowerCase();
                var downloadUrl = btn.getAttribute('data-download-url') || '';
                var isPdf = mime.indexOf('pdf') !== -1 || fileName.slice(-4) === '.pdf' || url
                    .toLowerCase().indexOf('.pdf') !== -1;
                var isDocx = mime.indexOf('wordprocessingml') !== -1 || fileName.slice(-5) === '.docx';
                var isLegacyDoc = !isDocx && (mime === 'application/msword' || fileName.slice(-4) ===
                    '.doc');

                title.textContent = btn.getAttribute('data-title') || 'Preview Dokumen';
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');

                frame.style.display = 'none';
                frame.setAttribute('src', '');
                pages.innerHTML = '';
                if (docxBox) {
                    docxBox.innerHTML = '';
                    docxBox.style.display = 'none';
                }
                loading.textContent = 'Memuat dokumen...';
                loading.style.display = 'block';

                if (isDocx && docxBox) {
                    try {
                        await ensureDocxLib();
                        var docxResponse = await fetch(url, {
                            credentials: 'same-origin'
                        });
                        var docxBuffer = await docxResponse.arrayBuffer();
                        loading.style.display = 'none';
                        docxBox.style.display = 'block';
                        await window.docx.renderAsync(docxBuffer, docxBox, null, {
                            inWrapper: true,
                            breakPages: true
                        });
                    } catch (error) {
                        docxBox.style.display = 'none';
                        loading.style.display = 'block';
                        loading.textContent =
                            'Dokumen tidak bisa ditampilkan di sini. Silakan unduh template untuk membukanya.';
                    }
                } else if (isLegacyDoc) {
                    loading.style.display = 'block';
                    loading.textContent =
                        'Format .doc lama tidak bisa dipratinjau di browser. Silakan unduh template untuk membukanya.';
                } else if (isPdf && pdfjsLib) {
                    try {
                        var response = await fetch(url, {
                            credentials: 'same-origin'
                        });
                        var buffer = await response.arrayBuffer();
                        var pdf = await pdfjsLib.getDocument({
                            data: buffer
                        }).promise;

                        for (var i = 1; i <= pdf.numPages; i++) {
                            var page = await pdf.getPage(i);
                            var viewport = page.getViewport({
                                scale: 1.3
                            });
                            var canvas = document.createElement('canvas');
                            var context = canvas.getContext('2d');
                            canvas.width = viewport.width;
                            canvas.height = viewport.height;
                            await page.render({
                                canvasContext: context,
                                viewport: viewport
                            }).promise;
                            pages.appendChild(canvas);
                        }
                        loading.style.display = 'none';
                    } catch (error) {
                        loading.style.display = 'none';
                        frame.style.display = 'block';
                        frame.setAttribute('src', url +
                        '#toolbar=0&navpanes=0&scrollbar=0');
                    }
                } else {
                    loading.style.display = 'none';
                    frame.style.display = 'block';
                    frame.setAttribute('src', url + '#toolbar=0&navpanes=0&scrollbar=0');
                }
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
    })();
</script>
@endsection
