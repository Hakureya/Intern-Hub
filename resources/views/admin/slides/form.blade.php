@extends('layouts.master')

@php
    $card = $slide->card_data;
    $copy = $slide->copy_data;
    $hasOld = session()->hasOldInput();
    $chk = fn($path, $default) => $hasOld ? (bool) old($path) : (bool) $default;
    $items = $hasOld ? array_values((array) old('copy.items', [])) : $copy['items'];
    $positions = [
        'top-left' => 'Kiri atas', 'top-center' => 'Tengah atas', 'top-right' => 'Kanan atas',
        'middle-left' => 'Kiri tengah', 'middle-center' => 'Tengah', 'middle-right' => 'Kanan tengah',
        'bottom-left' => 'Kiri bawah', 'bottom-center' => 'Tengah bawah', 'bottom-right' => 'Kanan bawah',
    ];
    $cardPosition = old('card.position', $card['position']);
@endphp

@section('css')
    <style>
        /*
         * Tema membungkus konten dengan overflow:hidden (#wrapper, .content-page, body di layar kecil).
         * Itu menjadikan pembungkus sebagai "scroll container" sehingga position:sticky tidak berfungsi.
         * overflow:clip tetap memotong konten tetapi tidak membuat scroll container; baris visible
         * dipakai sebagai cadangan untuk browser lama.
         */
        #wrapper,
        .content-page {
            overflow: visible !important;
            overflow: clip !important;
        }

        .content-page {
            display: flow-root;
        }

        body {
            overflow: visible !important;
        }

        /* Pratinjau selalu menempel di bawah topbar saat halaman di-scroll */
        /* .card dari Bootstrap memberi position:relative dan dimuat SETELAH CSS ini, jadi perlu !important */
        .card.sl-preview {
            position: -webkit-sticky !important;
            position: sticky !important;
            top: 78px;
            z-index: 5;
            margin-bottom: 20px;
        }

        @media (min-width: 1200px) {
            .card.sl-preview {
                top: 84px;
            }
        }

        /* Saat form dan pratinjau bertumpuk, pratinjau dibuat ringkas agar tidak menutupi form */
        @media (max-width: 1199.98px) {
            .sl-preview .card-body {
                padding: 10px 12px;
            }

            .sl-preview-bar {
                margin-bottom: 8px;
            }

            .sl-preview-bar h5 {
                font-size: 14px;
            }

            .sl-preview-note {
                display: none;
            }
        }

        .sl-preview-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
        }

        .sl-preview-frame-wrap {
            width: 100%;
            overflow: hidden;
            border: 1px solid #d9e7ef;
            border-radius: 10px;
            background: #e4eff5;
        }

        .sl-preview-frame {
            display: block;
            border: 0;
            transform-origin: top left;
            background: #e4eff5;
        }

        .sl-section-title {
            margin: 0 0 14px;
            font-size: 15px;
            font-weight: 700;
        }

        .sl-group {
            padding: 14px;
            margin-bottom: 14px;
            border: 1px solid #e3ebf1;
            border-radius: 10px;
            background: #fafcfd;
        }

        .sl-group-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .sl-style-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .sl-style-grid label {
            display: block;
            margin-bottom: 4px;
            font-size: 11px;
            color: #6b7c8c;
        }

        .sl-style-grid input[type="color"],
        .sl-color {
            width: 100%;
            max-width: 90px;
            height: 32px;
            padding: 2px;
            border: 1px solid #ced9e2;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
        }

        .sl-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 10px;
        }

        .sl-check {
            margin: 0;
            font-size: 12px;
            cursor: pointer;
        }

        .sl-check input {
            margin-right: 5px;
        }

        .sl-field {
            margin-bottom: 14px;
        }

        .sl-field>label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 700;
        }

        .sl-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .sl-inline>div {
            flex: 1 1 140px;
        }

        .sl-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sl-range input[type="range"] {
            flex: 1 1 auto;
        }

        .sl-range-val {
            min-width: 44px;
            font-size: 12px;
            text-align: right;
            color: #44596b;
        }

        .sl-pos {
            display: grid;
            grid-template-columns: repeat(3, 44px);
            gap: 6px;
        }

        .sl-pos label {
            position: relative;
            margin: 0;
            cursor: pointer;
        }

        .sl-pos input {
            position: absolute;
            opacity: 0;
        }

        .sl-pos span {
            display: block;
            height: 32px;
            border: 1px solid #ced9e2;
            border-radius: 6px;
            background: #fff;
        }

        .sl-pos input:checked+span {
            border-color: #1b6aa8;
            background: #1b6aa8;
        }

        .sl-pos input:focus-visible+span {
            outline: 3px solid #a9d0ea;
        }

        .sl-item {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #e3ebf1;
            border-radius: 8px;
            background: #fff;
        }

        .sl-item-fields {
            flex: 1 1 auto;
            display: grid;
            gap: 6px;
        }

        .sl-current-image {
            display: block;
            width: 160px;
            height: 100px;
            margin-bottom: 8px;
            border: 1px solid #d9e7ef;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
        }

        .sl-muted {
            color: #8a98a5;
            font-size: 12px;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">{{ $isNew ? 'Tambah Slide' : 'Ubah Slide' }}</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('slides.index') }}">Manajemen Slide</a></li>
            <li class="breadcrumb-item active">{{ $isNew ? 'Tambah' : 'Ubah' }}</li>
        </ol>
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" id="slideForm" enctype="multipart/form-data"
        action="{{ $isNew ? route('slides.store') : route('slides.update', $slide->id) }}">
        @csrf
        @unless ($isNew)
            @method('PATCH')
        @endunless

        <div class="row">
            {{-- PRATINJAU --}}
            <div class="col-xl-6 order-1 order-xl-2">
                <div class="card sl-preview">
                    <div class="card-body">
                        <div class="sl-preview-bar">
                            <h5 class="mb-0 mr-auto">Pratinjau langsung</h5>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Ukuran pratinjau">
                                <button type="button" class="btn btn-primary js-device" data-device="desktop">Desktop</button>
                                <button type="button" class="btn btn-outline-primary js-device" data-device="tablet">Tablet</button>
                                <button type="button" class="btn btn-outline-primary js-device" data-device="mobile">Ponsel</button>
                            </div>
                        </div>
                        <div class="sl-preview-frame-wrap" id="previewHolder">
                            <iframe id="previewFrame" class="sl-preview-frame" title="Pratinjau slide"></iframe>
                        </div>
                        <p class="sl-muted sl-preview-note mt-2 mb-0">Pratinjau memakai tampilan yang sama dengan landing page dan
                            berubah saat kamu mengetik.</p>
                    </div>
                </div>
            </div>

            {{-- FORM --}}
            <div class="col-xl-6 order-2 order-xl-1">
                <div class="card">
                    <div class="card-body">
                        <h5 class="sl-section-title">Pengaturan slide</h5>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                value="1" {{ $chk('is_active', $slide->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Tampilkan slide ini di landing page</label>
                        </div>
                    </div>
                </div>

                {{-- GAMBAR & LATAR --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="sl-section-title">Gambar &amp; latar</h5>

                        @if ($slide->image_url)
                            <span class="sl-current-image" style="background-image:url('{{ $slide->image_url }}')"></span>
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="remove_image"
                                    name="remove_image" value="1" {{ $chk('remove_image', false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="remove_image">Hapus gambar (pakai warna latar)</label>
                            </div>
                        @endif

                        <div class="sl-field">
                            <label for="image">{{ $slide->image_url ? 'Ganti gambar' : 'Unggah gambar' }}</label>
                            <input type="file" class="form-control-file" id="image" name="image"
                                accept=".jpg,.jpeg,.png,.webp">
                            <small class="form-text text-muted">JPG, PNG, atau WEBP, maksimal 3 MB. Kosongkan jika slide
                                tanpa gambar.</small>
                            <small class="form-text text-danger" id="imageWarn" style="display:none">Ukuran gambar lebih
                                dari 3 MB, pilih gambar yang lebih kecil.</small>
                        </div>

                        <div class="sl-field">
                            <label for="image_alt">Teks alternatif gambar</label>
                            <input type="text" class="form-control form-control-sm" id="image_alt" name="image_alt"
                                maxlength="200" value="{{ old('image_alt', $slide->image_alt) }}"
                                placeholder="Deskripsi singkat gambar untuk pembaca layar">
                        </div>

                        <div class="sl-inline">
                            <div class="sl-field">
                                <label for="image_fit">Ukuran gambar</label>
                                <select class="form-control form-control-sm" id="image_fit" name="image_fit">
                                    <option value="cover" {{ old('image_fit', $slide->image_fit) === 'cover' ? 'selected' : '' }}>Penuhi area (dipotong)</option>
                                    <option value="contain" {{ old('image_fit', $slide->image_fit) === 'contain' ? 'selected' : '' }}>Tampil utuh</option>
                                </select>
                            </div>
                            <div class="sl-field">
                                <label for="image_position">Fokus gambar</label>
                                <select class="form-control form-control-sm" id="image_position" name="image_position">
                                    @foreach (['center' => 'Tengah', 'top' => 'Atas', 'bottom' => 'Bawah', 'left' => 'Kiri', 'right' => 'Kanan'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('image_position', $slide->image_position) === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sl-field">
                                <label for="bg_color">Warna latar</label>
                                <input type="color" class="sl-color" id="bg_color" name="bg_color"
                                    value="{{ old('bg_color', $slide->bg_color) }}">
                            </div>
                        </div>
                        <p class="sl-muted mb-0">Warna latar dipakai saat slide tidak memiliki gambar, atau saat gambar
                            ditampilkan utuh.</p>
                    </div>
                </div>

                {{-- KARTU --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="sl-section-title">Kartu di atas gambar</h5>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="card_enabled" name="card[enabled]"
                                value="1" {{ $chk('card.enabled', $card['enabled']) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="card_enabled">Tampilkan kartu</label>
                        </div>

                        <div class="sl-inline">
                            <div class="sl-field">
                                <label>Letak kartu</label>
                                <div class="sl-pos" role="radiogroup" aria-label="Letak kartu">
                                    @foreach ($positions as $value => $label)
                                        <label title="{{ $label }}">
                                            <input type="radio" name="card[position]" value="{{ $value }}"
                                                {{ $cardPosition === $value ? 'checked' : '' }}>
                                            <span></span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sl-field">
                                <label for="card_width">Lebar kartu</label>
                                <div class="sl-range">
                                    <input type="range" id="card_width" name="card[width]" min="30" max="100"
                                        step="1" value="{{ old('card.width', $card['width']) }}">
                                    <span class="sl-range-val">{{ old('card.width', $card['width']) }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="sl-inline">
                            <div class="sl-field">
                                <label for="card_bg_color">Warna latar kartu</label>
                                <input type="color" class="sl-color" id="card_bg_color" name="card[bg_color]"
                                    value="{{ old('card.bg_color', $card['bg_color']) }}">
                            </div>
                            <div class="sl-field">
                                <label for="card_bg_opacity">Ketebalan warna kartu</label>
                                <div class="sl-range">
                                    <input type="range" id="card_bg_opacity" name="card[bg_opacity]" min="0"
                                        max="100" step="1" value="{{ old('card.bg_opacity', $card['bg_opacity']) }}">
                                    <span class="sl-range-val">{{ old('card.bg_opacity', $card['bg_opacity']) }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="sl-inline">
                            <div class="sl-field">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="card_quote_mark"
                                        name="card[quote_mark]" value="1"
                                        {{ $chk('card.quote_mark', $card['quote_mark']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="card_quote_mark">Tampilkan tanda kutip</label>
                                </div>
                            </div>
                            <div class="sl-field">
                                <label for="card_quote_color">Warna tanda kutip</label>
                                <input type="color" class="sl-color" id="card_quote_color" name="card[quote_color]"
                                    value="{{ old('card.quote_color', $card['quote_color']) }}">
                            </div>
                        </div>

                        @include('admin.slides._text-style', ['path' => 'card.label', 'label' => 'Label kecil (opsional)', 'data' => $card['label'], 'min' => 8, 'max' => 24, 'placeholder' => 'Mis. Kantor PLN SC'])
                        @include('admin.slides._text-style', ['path' => 'card.title', 'label' => 'Judul / isi utama kartu', 'data' => $card['title'], 'textarea' => true, 'rows' => 3, 'min' => 10, 'max' => 48, 'placeholder' => 'Teks utama pada kartu'])
                        @include('admin.slides._text-style', ['path' => 'card.subtitle', 'label' => 'Teks bawah kartu (opsional)', 'data' => $card['subtitle'], 'min' => 8, 'max' => 28, 'placeholder' => 'Keterangan tambahan'])
                    </div>
                </div>

                {{-- TEKS SAMPING --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="sl-section-title">Teks di samping slide</h5>

                        <div class="sl-field">
                            <label for="copy_align">Perataan teks</label>
                            <select class="form-control form-control-sm" id="copy_align" name="copy[align]"
                                style="max-width:200px">
                                @foreach (['left' => 'Rata kiri', 'center' => 'Tengah', 'right' => 'Rata kanan'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('copy.align', $copy['align']) === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        @include('admin.slides._text-style', ['path' => 'copy.eyebrow', 'label' => 'Judul kecil (di atas judul)', 'data' => $copy['eyebrow'], 'min' => 8, 'max' => 24, 'placeholder' => 'Mis. Tentang Kami'])
                        @include('admin.slides._text-style', ['path' => 'copy.title', 'label' => 'Judul utama', 'data' => $copy['title'], 'textarea' => true, 'rows' => 2, 'accent' => true, 'min' => 20, 'max' => 72, 'hint' => 'Tekan Enter untuk baris baru. Apit kata dengan *bintang* untuk mewarnainya dengan warna aksen.', 'placeholder' => "Mendukung listrik\nIndonesia tetap *andal.*"])
                        @include('admin.slides._text-style', ['path' => 'copy.body', 'label' => 'Isi penjelasan', 'data' => $copy['body'], 'textarea' => true, 'rows' => 6, 'min' => 11, 'max' => 24, 'hint' => 'Setiap baris baru menjadi satu paragraf.', 'placeholder' => 'Tulis penjelasan slide'])

                        {{-- Daftar poin --}}
                        <div class="sl-group">
                            <span class="sl-group-label">Daftar poin bernomor (opsional)</span>
                            <div id="itemsList">
                                @foreach ($items as $n => $item)
                                    <div class="sl-item js-item-row">
                                        <div class="sl-item-fields">
                                            <input type="text" class="form-control form-control-sm js-item-title"
                                                name="copy[items][{{ $n }}][title]" maxlength="120"
                                                value="{{ $item['title'] ?? '' }}" placeholder="Judul poin">
                                            <textarea class="form-control form-control-sm js-item-text" name="copy[items][{{ $n }}][text]"
                                                rows="2" maxlength="300" placeholder="Penjelasan singkat">{{ $item['text'] ?? '' }}</textarea>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger js-item-remove">Hapus</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">+ Tambah poin</button>
                            <span class="sl-muted ml-2">Maksimal 8 poin.</span>

                            <div class="sl-style-grid">
                                <div>
                                    <label for="items_number_color">Warna nomor</label>
                                    <input type="color" id="items_number_color" name="copy[items_style][number_color]"
                                        value="{{ old('copy.items_style.number_color', $copy['items_style']['number_color']) }}">
                                </div>
                                <div>
                                    <label for="items_title_color">Warna judul poin</label>
                                    <input type="color" id="items_title_color" name="copy[items_style][title_color]"
                                        value="{{ old('copy.items_style.title_color', $copy['items_style']['title_color']) }}">
                                </div>
                                <div>
                                    <label for="items_text_color">Warna penjelasan</label>
                                    <input type="color" id="items_text_color" name="copy[items_style][text_color]"
                                        value="{{ old('copy.items_style.text_color', $copy['items_style']['text_color']) }}">
                                </div>
                            </div>
                        </div>

                        {{-- Profil orang --}}
                        <div class="sl-group">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="person_enabled"
                                    name="copy[person][enabled]" value="1"
                                    {{ $chk('copy.person.enabled', $copy['person']['enabled']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="person_enabled">Tampilkan profil orang (inisial,
                                    nama, keterangan)</label>
                            </div>
                            <div class="sl-field">
                                <label for="person_name">Nama</label>
                                <input type="text" class="form-control form-control-sm" id="person_name"
                                    name="copy[person][name]" maxlength="120"
                                    value="{{ old('copy.person.name', $copy['person']['name']) }}">
                            </div>
                            <div class="sl-field">
                                <label for="person_subtitle">Keterangan</label>
                                <input type="text" class="form-control form-control-sm" id="person_subtitle"
                                    name="copy[person][subtitle]" maxlength="160"
                                    value="{{ old('copy.person.subtitle', $copy['person']['subtitle']) }}">
                            </div>
                            <div class="sl-style-grid">
                                <div>
                                    <label for="person_name_color">Warna nama</label>
                                    <input type="color" id="person_name_color" name="copy[person][name_color]"
                                        value="{{ old('copy.person.name_color', $copy['person']['name_color']) }}">
                                </div>
                                <div>
                                    <label for="person_subtitle_color">Warna keterangan</label>
                                    <input type="color" id="person_subtitle_color" name="copy[person][subtitle_color]"
                                        value="{{ old('copy.person.subtitle_color', $copy['person']['subtitle_color']) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <button type="submit" class="btn btn-primary">{{ $isNew ? 'Simpan Slide' : 'Simpan Perubahan' }}</button>
                    <a href="{{ route('slides.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script-bottom')
    <script>
        (function() {
            var form = document.getElementById('slideForm');
            var frame = document.getElementById('previewFrame');
            var holder = document.getElementById('previewHolder');
            var DEVICES = {
                desktop: 1100,
                tablet: 768,
                mobile: 390
            };
            var FONTS = {
                display: 'var(--cs-display)',
                body: 'var(--cs-font)',
                serif: 'Georgia, serif'
            };
            var CSS_CORE = @json(asset('css/cerah.css'));
            var CSS_PUBLIC = @json(asset('css/cerah-public.css'));
            var existingImage = @json($slide->image_url);
            var device = 'desktop';
            var blobUrl = null;
            var ready = false;
            var queued = false;
            var itemIndex = @json(count($items) + 100);

            /* ---------- helpers ---------- */
            function esc(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                    '&quot;');
            }

            function el(name) {
                return form.elements[name];
            }

            function val(name) {
                var e = el(name);
                return e ? e.value : '';
            }

            function chk(name) {
                var e = el(name);
                return !!(e && e.checked);
            }

            function num(name, def) {
                var n = parseInt(val(name), 10);
                return isNaN(n) ? def : n;
            }

            // Cerminan LandingSlide::textCss()
            function textCss(p) {
                return 'color:' + val(p + '[color]') + ';font-size:' + num(p + '[size]', 14) + 'px;font-weight:' +
                    val(p + '[weight]') + ';font-style:' + (chk(p + '[italic]') ? 'italic' : 'normal') +
                    ';text-transform:' + (chk(p + '[uppercase]') ? 'uppercase' : 'none') + ';font-family:' +
                    (FONTS[val(p + '[font]')] || FONTS.body) + ';';
            }

            // Cerminan LandingSlide::cardPositionCss()
            function cardPos(pos, width) {
                var p = pos.split('-'),
                    v = p[0],
                    h = p[1];
                var css = 'width:' + width + '%;min-width:min(240px,90%);';
                css += h === 'left' ? 'left:5%;right:auto;' : (h === 'right' ? 'left:auto;right:5%;' :
                    'left:50%;right:auto;');
                css += v === 'top' ? 'top:5%;bottom:auto;' : (v === 'bottom' ? 'top:auto;bottom:5%;' :
                    'top:50%;bottom:auto;');
                var tx = h === 'center' ? '-50%' : '0',
                    ty = v === 'middle' ? '-50%' : '0';
                if (tx !== '0' || ty !== '0') css += 'transform:translate(' + tx + ',' + ty + ');';
                return css;
            }

            function cardBg() {
                var hex = (val('card[bg_color]') || '#fffdf7').replace('#', '');
                var a = Math.round(num('card[bg_opacity]', 96)) / 100;
                return 'rgba(' + parseInt(hex.substr(0, 2), 16) + ',' + parseInt(hex.substr(2, 2), 16) + ',' +
                    parseInt(hex.substr(4, 2), 16) + ',' + a + ')';
            }

            // Cerminan LandingSlide::formatTitle()
            function formatTitle(text, accent) {
                var html = esc(text).replace(/\*([\s\S]+?)\*/g, function(m, t) {
                    return '<span class="cs-accent" style="color:' + accent + '">' + t + '</span>';
                });
                return html.replace(/\n/g, '<br>');
            }

            function paragraphs(text) {
                return String(text).split(/\n\s*\n|\n/).map(function(s) {
                    return s.trim();
                }).filter(Boolean);
            }

            function initials(name) {
                return name.trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function(w) {
                    return w.charAt(0).toUpperCase();
                }).join('');
            }

            function imageSrc() {
                var input = el('image');
                if (input && input.files && input.files[0]) return blobUrl;
                if (chk('remove_image')) return '';
                return existingImage || '';
            }

            /* ---------- HTML slide (cerminan magang/partials/about-slide.blade.php) ---------- */
            function slideHtml() {
                var align = val('copy[align]') || 'left';
                var justify = {
                    left: 'flex-start',
                    center: 'center',
                    right: 'flex-end'
                } [align];
                var pMargin = {
                    left: '0 0 12px',
                    center: '0 auto 12px',
                    right: '0 0 12px auto'
                } [align];
                var h = '<div class="cs-slide is-active">';

                // visual
                h += '<div class="cs-why-visual" style="background:' + esc(val('bg_color')) + ';">';
                var src = imageSrc();
                if (src) {
                    h += '<img src="' + esc(src) + '" alt="" style="object-fit:' + esc(val('image_fit')) +
                        ';object-position:' + esc(val('image_position')) + ';">';
                }
                var label = val('card[label][text]'),
                    title = val('card[title][text]'),
                    sub = val('card[subtitle][text]');
                if (chk('card[enabled]') && (label || title || sub)) {
                    h += '<div class="cs-photo-card' + (chk('card[quote_mark]') ? ' is-quote' : '') + '" style="' +
                        cardPos(val('card[position]') || 'bottom-center', num('card[width]', 90)) +
                        'background:' + cardBg() + ';">';
                    if (chk('card[quote_mark]')) {
                        h += '<span class="cs-quote-mark" style="font:800 34px/0.6 Georgia,serif;letter-spacing:normal;text-transform:none;color:' +
                            esc(val('card[quote_color]')) + ';">“</span>';
                    }
                    if (label) h += '<span style="' + textCss('card[label]') + '">' + esc(label) + '</span>';
                    if (title) h += '<strong style="' + textCss('card[title]') + '">' + esc(title) + '</strong>';
                    if (sub) h += '<small style="' + textCss('card[subtitle]') + '">' + esc(sub) + '</small>';
                    h += '</div>';
                }
                h += '</div>';

                // teks samping
                h += '<div class="cs-why-copy" style="text-align:' + align + ';">';
                var eyebrow = val('copy[eyebrow][text]');
                if (eyebrow) h += '<div class="cs-eyebrow" style="' + textCss('copy[eyebrow]') + '">' + esc(eyebrow) +
                    '</div>';

                var ttl = val('copy[title][text]');
                if (ttl) {
                    var size = num('copy[title][size]', 44);
                    h += '<h2 style="' + textCss('copy[title]') + 'letter-spacing:-.045em;font-size:clamp(' +
                        Math.round(size * 0.68) + 'px,' + (Math.round(size / 11 * 100) / 100) + 'vw,' + size + 'px);">' +
                        formatTitle(ttl, val('copy[title][accent_color]')) + '</h2>';
                }

                paragraphs(val('copy[body][text]')).forEach(function(p) {
                    h += '<p style="' + textCss('copy[body]') + 'margin:' + pMargin + ';">' + esc(p) + '</p>';
                });

                var n = 0;
                form.querySelectorAll('.js-item-row').forEach(function(row) {
                    var it = row.querySelector('.js-item-title').value.trim();
                    var tx = row.querySelector('.js-item-text').value.trim();
                    if (!it && !tx) return;
                    n++;
                    h += '<div class="cs-benefit" style="justify-content:' + justify + ';"><span style="color:' +
                        esc(val('copy[items_style][number_color]')) + ';">' + (n < 10 ? '0' + n : n) +
                        '</span><div><strong style="color:' + esc(val('copy[items_style][title_color]')) + ';">' +
                        esc(it) + '</strong><p style="color:' + esc(val('copy[items_style][text_color]')) + ';">' +
                        esc(tx) + '</p></div></div>';
                });

                var pname = val('copy[person][name]').trim();
                if (chk('copy[person][enabled]') && pname) {
                    h += '<div class="cs-person" style="justify-content:' + justify + ';"><div class="cs-avatar">' +
                        esc(initials(pname)) + '</div><div><strong style="color:' + esc(val(
                            'copy[person][name_color]')) + ';">' + esc(pname) + '</strong>';
                    var psub = val('copy[person][subtitle]').trim();
                    if (psub) h += '<small style="color:' + esc(val('copy[person][subtitle_color]')) + ';">' + esc(
                        psub) + '</small>';
                    h += '</div></div>';
                }
                h += '</div></div>';
                return h;
            }

            /* ---------- iframe pratinjau ---------- */
            frame.srcdoc = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">' +
                '<meta name="viewport" content="width=device-width, initial-scale=1">' +
                '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">' +
                '<link href="' + CSS_CORE + '" rel="stylesheet"><link href="' + CSS_PUBLIC + '" rel="stylesheet">' +
                '<style>html,body{margin:0}body{background:#e4eff5}</style></head>' +
                '<body><section class="cs-section cs-why"><div class="cs-slides" id="stage"></div></section></body></html>';

            function resize() {
                var doc = frame.contentDocument;
                if (!doc || !doc.querySelector('section')) return;
                var w = DEVICES[device];
                var h = doc.querySelector('section').offsetHeight;
                var scale = Math.min(1, holder.clientWidth / w);

                // Saat bertumpuk (layar < 1200px), batasi tinggi pratinjau agar form tetap terlihat.
                var stacked = window.matchMedia && window.matchMedia('(max-width: 1199.98px)').matches;
                if (stacked && h > 0) {
                    scale = Math.min(scale, (window.innerHeight * 0.34) / h);
                }

                frame.style.width = w + 'px';
                frame.style.height = h + 'px';
                frame.style.transform = 'scale(' + scale + ')';
                frame.style.marginLeft = Math.max(0, (holder.clientWidth - w * scale) / 2) + 'px';
                holder.style.height = Math.ceil(h * scale) + 'px';
            }

            function render() {
                queued = false;
                var doc = frame.contentDocument;
                if (!ready || !doc) return;
                var stage = doc.getElementById('stage');
                if (!stage) return;
                stage.innerHTML = slideHtml();
                stage.querySelectorAll('img').forEach(function(img) {
                    img.addEventListener('load', resize);
                });
                resize();
            }

            function schedule() {
                if (queued) return;
                queued = true;
                window.requestAnimationFrame(render);
            }

            frame.addEventListener('load', function() {
                ready = true;
                render();
                setTimeout(resize, 400);
                setTimeout(resize, 1200);
            });

            window.addEventListener('resize', resize);
            form.addEventListener('input', schedule);
            form.addEventListener('change', schedule);

            /* ---------- ukuran pratinjau ---------- */
            document.querySelectorAll('.js-device').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    device = btn.getAttribute('data-device');
                    document.querySelectorAll('.js-device').forEach(function(b) {
                        var on = b === btn;
                        b.classList.toggle('btn-primary', on);
                        b.classList.toggle('btn-outline-primary', !on);
                    });
                    resize();
                });
            });

            /* ---------- nilai slider ---------- */
            form.querySelectorAll('input[type="range"]').forEach(function(r) {
                var out = r.parentNode.querySelector('.sl-range-val');
                r.addEventListener('input', function() {
                    if (out) out.textContent = r.value + '%';
                });
            });

            /* ---------- gambar baru ---------- */
            var fileInput = el('image');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (blobUrl) URL.revokeObjectURL(blobUrl);
                    blobUrl = null;
                    var f = fileInput.files && fileInput.files[0];
                    document.getElementById('imageWarn').style.display = f && f.size > 3 * 1024 * 1024 ?
                        'block' : 'none';
                    if (f) blobUrl = URL.createObjectURL(f);
                    schedule();
                });
            }

            /* ---------- daftar poin ---------- */
            var list = document.getElementById('itemsList');
            var addBtn = document.getElementById('addItem');

            function syncAddBtn() {
                addBtn.style.display = list.querySelectorAll('.js-item-row').length >= 8 ? 'none' : '';
            }

            addBtn.addEventListener('click', function() {
                var i = itemIndex++;
                var row = document.createElement('div');
                row.className = 'sl-item js-item-row';
                row.innerHTML = '<div class="sl-item-fields">' +
                    '<input type="text" class="form-control form-control-sm js-item-title" name="copy[items][' + i +
                    '][title]" maxlength="120" placeholder="Judul poin">' +
                    '<textarea class="form-control form-control-sm js-item-text" name="copy[items][' + i +
                    '][text]" rows="2" maxlength="300" placeholder="Penjelasan singkat"></textarea></div>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger js-item-remove">Hapus</button>';
                list.appendChild(row);
                syncAddBtn();
                schedule();
            });

            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('js-item-remove')) {
                    e.target.closest('.js-item-row').remove();
                    syncAddBtn();
                    schedule();
                }
            });

            syncAddBtn();

            /* ---------- Cadangan: tempel pratinjau lewat JS jika CSS sticky tidak bekerja ---------- */
            var stickyCard = document.querySelector('.sl-preview');
            var stickyCol = stickyCard ? stickyCard.parentNode : null;
            var fallbackOn = false;
            var cardH = 0;

            function stickyTop() {
                return window.matchMedia('(min-width: 1200px)').matches ? 84 : 78;
            }

            function clearFallback() {
                ['position', 'top', 'left', 'right', 'bottom', 'width'].forEach(function(prop) {
                    stickyCard.style.removeProperty(prop);
                });
                stickyCol.style.minHeight = '';
            }

            function checkSticky() {
                if (!stickyCard || !stickyCol) return;
                var top = stickyTop();
                var col = stickyCol.getBoundingClientRect();

                if (!fallbackOn) {
                    // Kolom sudah melewati batas atas tetapi kartu tidak ikut menempel => CSS sticky gagal.
                    var card = stickyCard.getBoundingClientRect();
                    if (col.top < top - 4 && card.top > top + 6) {
                        fallbackOn = true;
                    } else {
                        return;
                    }
                }

                var cs = window.getComputedStyle(stickyCol);
                var padL = parseFloat(cs.paddingLeft) || 0;
                var padR = parseFloat(cs.paddingRight) || 0;

                // Ukur tinggi kartu hanya saat masih berada di alur normal, lalu tahan tinggi kolom
                // agar konten di bawahnya tidak melompat ketika kartu dilepas dari alur.
                if (!stickyCard.style.position) cardH = stickyCard.offsetHeight;
                var height = cardH;

                stickyCol.style.position = 'relative';
                stickyCol.style.minHeight = height + 'px';

                if (col.top >= top) {
                    clearFallback();
                    stickyCol.style.minHeight = '';
                } else if (col.bottom - height <= top) {
                    stickyCard.style.setProperty('position', 'absolute', 'important');
                    stickyCard.style.top = 'auto';
                    stickyCard.style.left = padL + 'px';
                    stickyCard.style.right = padR + 'px';
                    stickyCard.style.bottom = '0';
                    stickyCard.style.width = 'auto';
                } else {
                    stickyCard.style.setProperty('position', 'fixed', 'important');
                    stickyCard.style.top = top + 'px';
                    stickyCard.style.left = (col.left + padL) + 'px';
                    stickyCard.style.right = 'auto';
                    stickyCard.style.bottom = 'auto';
                    stickyCard.style.width = (col.width - padL - padR) + 'px';
                }
            }

            // capture=true agar scroll di elemen mana pun (bukan hanya window) ikut terdeteksi
            document.addEventListener('scroll', checkSticky, true);
            window.addEventListener('resize', checkSticky);
        })();
    </script>
@endsection
