@extends('layouts.master')

@section('css')
    <style>
        .template-preview-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(12, 22, 40, 0.35);
            backdrop-filter: blur(6px);
            padding: 18px;
        }

        .template-preview-modal.show {
            display: flex;
        }

        .template-preview-card {
            width: min(1020px, 100%);
            height: min(86vh, 780px);
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(7, 52, 84, 0.35);
            display: flex;
            flex-direction: column;
        }

        .template-preview-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 14px;
        }

        .template-preview-title {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
        }

        .template-preview-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: none;
        }

        .template-preview-body {
            width: 100%;
            height: 100%;
            overflow: auto;
            background: #f3f4f6;
            padding: 16px;
        }

        .template-pdf-pages {
            display: grid;
            gap: 12px;
            justify-content: center;
        }

        .template-pdf-pages canvas {
            background: #fff;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.14);
            max-width: 100%;
            height: auto;
        }

        .template-preview-loading {
            font-size: 13px;
            color: #475569;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Draft Dokumen Magang</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Draft Dokumen Magang</a></li>
        </ol>
    </div>
@endsection

@section('content')
    @include('includes.flash')
    @php
        $documentTypeLabel = function ($type) {
            $map = [
                'monthly' => 'Template Laporan Bulanan',
                'final' => 'Template Laporan Akhir',
                'dossier' => 'Perpustakaan Dossier User',
            ];
            return $map[$type] ?? ucfirst((string) $type);
        };
        $libraryCategoryLabel = function ($key) {
            $map = [
                'compro' => 'Compro',
                'laporan_keuangan' => 'Laporan Keuangan',
                'pedoman_sop' => 'Pedoman / SOP',
                'materi_orientasi' => 'Materi Orientasi',
                'lainnya' => 'Lainnya',
            ];
            return $map[$key] ?? '-';
        };
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('internship-draft-documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="mb-1">Jenis Dokumen</label>
                    <select name="document_type" id="document_type" class="form-control" required>
                        <option value="">Pilih jenis</option>
                        <option value="monthly">Template Laporan Bulanan</option>
                        <option value="final">Template Laporan Akhir</option>
                        <option value="dossier">Perpustakaan Dossier User</option>
                    </select>
                </div>
                <div class="form-group" id="library-category-wrap" style="display:none;">
                    <label class="mb-1">Kategori Perpustakaan</label>
                    <select name="library_category" id="library_category" class="form-control">
                        <option value="">Pilih kategori</option>
                        <option value="compro">Compro</option>
                        <option value="laporan_keuangan">Laporan Keuangan</option>
                        <option value="pedoman_sop">Pedoman / SOP</option>
                        <option value="materi_orientasi">Materi Orientasi</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="mb-1">Judul Dokumen</label>
                    <input type="text" name="title" class="form-control"
                        placeholder="Contoh: Template Laporan Bulanan v1" required>
                </div>
                <div class="form-group">
                    <label class="mb-1">Deskripsi Singkat</label>
                    <textarea name="description" class="form-control" rows="6" maxlength="1000"
                        placeholder="Contoh:&#10;Template sederhana berisi ringkasan kegiatan, hasil pekerjaan, dan rencana berikutnya.&#10;- Identitas peserta dan periode&#10;- Ringkasan pekerjaan per minggu&#10;- Catatan dan validasi mentor">{{ old('description') }}</textarea>
                    <small class="form-text text-muted">
                        Untuk template laporan bulanan/akhir: tulis kalimat deskripsi di baris biasa, lalu poin isi
                        template satu per baris dengan awalan <code>-</code>. Yang tampil di halaman peserta adalah
                        dokumen terbaru dari tiap jenis.
                    </small>
                </div>
                <div class="form-group">
                    <label class="mb-1">File Dokumen</label>
                    <input type="file" name="draft_document" class="form-control-file" required>
                </div>
                <div class="form-group mb-0">
                    <button class="btn btn-primary btn-sm" type="submit">Tambah Dokumen</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('internship-draft-documents.index') }}" class="form-inline mb-3">
                <select class="form-control mr-2 mb-2" name="type">
                    <option value="">Semua Jenis</option>
                    <option value="monthly" {{ $selectedType === 'monthly' ? 'selected' : '' }}>Template Laporan Bulanan
                    </option>
                    <option value="final" {{ $selectedType === 'final' ? 'selected' : '' }}>Template Laporan Akhir</option>
                    <option value="dossier" {{ $selectedType === 'dossier' ? 'selected' : '' }}>Perpustakaan Dossier User
                    </option>
                </select>
                <button class="btn btn-primary btn-sm mb-2" type="submit">Tampilkan</button>
            </form>

            @if ($documents->isEmpty())
                <div class="text-muted">Belum ada draft dokumen magang.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal Upload</th>
                                <th>Jenis</th>
                                <th>Kategori</th>
                                <th>Judul</th>
                                <th>Deskripsi</th>
                                <th>Nama File</th>
                                <th>Pengunggah</th>
                                <th>Ukuran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $item)
                                <tr>
                                    <td>{{ optional($item->created_at)->format('d-m-Y H:i') }}</td>
                                    <td>{{ $documentTypeLabel($item->document_type) }}</td>
                                    <td>{{ $item->document_type === 'dossier' ? $libraryCategoryLabel($item->library_category) : '-' }}
                                    </td>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->description ?: '-', 70) }}</td>
                                    <td>{{ $item->file_name }}</td>
                                    <td>{{ optional($item->uploader)->name ?? '-' }}</td>
                                    <td>
                                        @php
                                            $size = (int) ($item->file_size ?? 0);
                                            $displaySize = $size > 0 ? number_format($size / 1024, 1) . ' KB' : '-';
                                        @endphp
                                        {{ $displaySize }}
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <button type="button" class="btn btn-info btn-sm btn-template-preview"
                                            data-title="{{ $item->title }}"
                                            data-preview-url="{{ route('internship-draft-documents.view', $item->id) }}"
                                            data-mime="{{ $item->mime_type ?? '' }}">
                                            Lihat
                                        </button>
                                        <a href="{{ route('internship-draft-documents.download', $item->id) }}"
                                            class="btn btn-success btn-sm">Unduh</a>
                                        <form method="POST"
                                            action="{{ route('internship-draft-documents.destroy', $item->id) }}"
                                            style="display:inline-block;"
                                            onsubmit="return confirm('Yakin ingin menghapus dokumen ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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
                <iframe id="template-preview-frame" class="template-preview-frame" src=""></iframe>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.5.136/build/pdf.min.mjs" type="module"></script>
    <script>
        (async function() {
            var typeSelect = document.getElementById('document_type');
            var libraryCategoryWrap = document.getElementById('library-category-wrap');
            var libraryCategoryInput = document.getElementById('library_category');
            if (typeSelect && libraryCategoryWrap && libraryCategoryInput) {
                function syncLibraryCategoryField() {
                    var isDossier = typeSelect.value === 'dossier';
                    libraryCategoryWrap.style.display = isDossier ? 'block' : 'none';
                    libraryCategoryInput.required = isDossier;
                    if (!isDossier) {
                        libraryCategoryInput.value = '';
                    }
                }
                typeSelect.addEventListener('change', syncLibraryCategoryField);
                syncLibraryCategoryField();
            }

            var modal = document.getElementById('template-preview-modal');
            var frame = document.getElementById('template-preview-frame');
            var title = document.getElementById('template-preview-title');
            var closeBtn = document.getElementById('template-preview-close');
            var loading = document.getElementById('template-preview-loading');
            var pages = document.getElementById('template-pdf-pages');

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

            function closePreview() {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                frame.setAttribute('src', '');
                frame.style.display = 'none';
                pages.innerHTML = '';
                loading.textContent = 'Memuat dokumen...';
                loading.style.display = 'block';
            }

            document.querySelectorAll('.btn-template-preview').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    var url = btn.getAttribute('data-preview-url') || '';
                    var mime = (btn.getAttribute('data-mime') || '').toLowerCase();
                    var isPdf = mime.indexOf('pdf') !== -1 || url.toLowerCase().indexOf(
                        '.pdf') !== -1;

                    title.textContent = btn.getAttribute('data-title') || 'Preview Dokumen';
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');

                    frame.style.display = 'none';
                    frame.setAttribute('src', '');
                    pages.innerHTML = '';
                    loading.textContent = 'Memuat dokumen...';
                    loading.style.display = 'block';

                    if (isPdf && pdfjsLib) {
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
