{{--
    Logo di pojok kiri atas.
    Jika ada gambar panjang public/images/brand/logo-panjang.{svg,png,webp,jpg,jpeg},
    gambar itu dipakai (teks sudah ada di dalam gambar). Jika tidak, tampil logo kotak + teks bawaan.
--}}
@php
    $brandImage = null;
    foreach (['svg', 'png', 'webp', 'jpg', 'jpeg'] as $brandExt) {
        $brandPath = 'images/brand/logo-panjang.' . $brandExt;
        if (file_exists(public_path($brandPath))) {
            $brandImage = asset($brandPath) . '?v=' . filemtime(public_path($brandPath));
            break;
        }
    }
@endphp
@if ($brandImage)
    <img src="{{ $brandImage }}" alt="PLN Suku Cadang" class="cs-brand-img">
@else
    <span class="cs-brand-mark"><img src="{{ asset('assets/images/logo-pln1.png') }}" alt="Logo PLN"></span>
    <span>PLN<span class="cs-brand-light"> Suku Cadang</span></span>
@endif
