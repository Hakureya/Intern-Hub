@extends('layouts.master')

@section('css')
    <style>
        .sl-settings {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-end;
        }

        .sl-settings .form-group {
            margin-bottom: 0;
        }

        .sl-thumb {
            width: 84px;
            height: 54px;
            border-radius: 8px;
            border: 1px solid #d9e7ef;
            background-size: cover;
            background-position: center;
            display: block;
        }

        .sl-order {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .sl-order form {
            margin: 0;
        }

        .sl-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .sl-actions form {
            margin: 0;
        }

        .sl-muted {
            color: #8a98a5;
            font-size: 12px;
        }
    </style>
@endsection

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Manajemen Slide</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href = "">Manajemen Slide</a></li>
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

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Kecepatan pergantian slide</h5>
            <form method="POST" action="{{ route('slides.settings') }}" class="sl-settings">
                @csrf
                <div class="form-group">
                    <label for="slide_interval">Ganti setiap (detik)</label>
                    <input type="number" class="form-control" id="slide_interval" name="slide_interval"
                        value="{{ old('slide_interval', $interval) }}" min="2" max="30" style="width:140px"
                        required>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="slide_autoplay" name="slide_autoplay"
                            value="1" {{ $autoplay ? 'checked' : '' }}>
                        <label class="custom-control-label" for="slide_autoplay">Ganti otomatis</label>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-sm">Simpan pengaturan</button>
                </div>
            </form>
            <p class="sl-muted mt-2 mb-0">Rentang 2–30 detik. Jika "Ganti otomatis" dimatikan, pengunjung berpindah slide
                lewat titik navigasi atau geser layar.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:10px">
                <h5 class="mb-0">Daftar slide</h5>
                <a href="{{ url('/') }}#tentang" target="_blank" rel="noopener"
                    class="btn btn-outline-secondary btn-sm ml-auto">Lihat di landing page</a>
                <a href="{{ route('slides.create') }}" class="btn btn-primary btn-sm"><i class="ti-plus"></i> Tambah
                    Slide</a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width:90px">Urutan</th>
                            <th style="width:110px">Latar</th>
                            <th>Judul</th>
                            <th style="width:110px">Status</th>
                            <th style="width:320px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($slides as $i => $slide)
                            @php
                                $copy = $slide->copy_data;
                                $card = $slide->card_data;
                                $title = trim(str_replace(['*', "\n"], ['', ' '], $copy['title']['text'])) ?: ($card['title']['text'] ?: '(tanpa judul)');
                            @endphp
                            <tr>
                                <td>
                                    <div class="sl-order">
                                        <span class="mr-1">{{ $i + 1 }}</span>
                                        <form method="POST" action="{{ route('slides.move', [$slide->id, 'up']) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light" title="Naikkan"
                                                {{ $i === 0 ? 'disabled' : '' }}>↑</button>
                                        </form>
                                        <form method="POST" action="{{ route('slides.move', [$slide->id, 'down']) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light" title="Turunkan"
                                                {{ $i === $slides->count() - 1 ? 'disabled' : '' }}>↓</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <span class="sl-thumb"
                                        style="background-color:{{ $slide->bg_color }};@if ($slide->image_url) background-image:url('{{ $slide->image_url }}'); @endif"></span>
                                </td>
                                <td>
                                    {{ $title }}
                                    @if ($copy['eyebrow']['text'])
                                        <div class="sl-muted">{{ $copy['eyebrow']['text'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $slide->is_active ? 'success' : 'warning' }}">
                                        {{ $slide->is_active ? 'Tampil' : 'Disembunyikan' }}</span>
                                </td>
                                <td>
                                    <div class="sl-actions">
                                        <a href="{{ route('slides.edit', $slide->id) }}"
                                            class="btn btn-sm btn-warning">Ubah</a>
                                        <form method="POST" action="{{ route('slides.toggle', $slide->id) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-sm {{ $slide->is_active ? 'btn-secondary' : 'btn-success' }}">
                                                {{ $slide->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('slides.destroy', $slide->id) }}"
                                            data-confirm="Hapus slide &quot;{{ $title }}&quot;? Tindakan ini tidak bisa dibatalkan.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada slide. Bagian "Tentang Kami" di
                                    landing page disembunyikan sampai ada slide yang tampil.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script-bottom')
    <script>
        (function($) {
            $(document).on('submit', 'form[data-confirm]', function() {
                return window.confirm($(this).data('confirm'));
            });
        })(jQuery);
    </script>
@endsection
