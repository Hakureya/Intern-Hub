{{--
    Kontrol satu elemen teks: isi teks + warna, ukuran, ketebalan, font, miring, huruf kapital.
    Parameter: $path (mis. 'card.title'), $label, $data (array nilai), opsional:
    $textarea, $rows, $hint, $accent (warna aksen judul), $min, $max, $placeholder
--}}
@php
    $segments = explode('.', $path);
    $base = array_shift($segments);
    $name = $base . implode('', array_map(fn($s) => '[' . $s . ']', $segments));
    $uid = str_replace('.', '_', $path);
    $hasOld = session()->hasOldInput();
    $val = fn($k) => old($path . '.' . $k, $data[$k] ?? '');
    $chk = fn($k) => $hasOld ? (bool) old($path . '.' . $k) : (bool) ($data[$k] ?? false);
    $weights = [400 => 'Normal', 500 => 'Sedang', 600 => 'Semi tebal', 700 => 'Tebal', 800 => 'Sangat tebal'];
    $fonts = ['display' => 'Manrope (judul)', 'body' => 'DM Sans (teks)', 'serif' => 'Georgia (serif)'];
@endphp
<div class="sl-group">
    <label class="sl-group-label" for="{{ $uid }}_text">{{ $label }}</label>
    @if (!empty($textarea))
        <textarea class="form-control form-control-sm" id="{{ $uid }}_text" name="{{ $name }}[text]"
            rows="{{ $rows ?? 3 }}" placeholder="{{ $placeholder ?? '' }}">{{ $val('text') }}</textarea>
    @else
        <input type="text" class="form-control form-control-sm" id="{{ $uid }}_text" name="{{ $name }}[text]"
            value="{{ $val('text') }}" placeholder="{{ $placeholder ?? '' }}">
    @endif
    @if (!empty($hint))
        <small class="form-text text-muted">{{ $hint }}</small>
    @endif

    <div class="sl-style-grid">
        <div>
            <label for="{{ $uid }}_color">Warna teks</label>
            <input type="color" id="{{ $uid }}_color" name="{{ $name }}[color]" value="{{ $val('color') }}">
        </div>
        @if (!empty($accent))
            <div>
                <label for="{{ $uid }}_accent">Warna aksen</label>
                <input type="color" id="{{ $uid }}_accent" name="{{ $name }}[accent_color]"
                    value="{{ $val('accent_color') }}">
            </div>
        @endif
        <div>
            <label for="{{ $uid }}_size">Ukuran (px)</label>
            <input type="number" class="form-control form-control-sm" id="{{ $uid }}_size"
                name="{{ $name }}[size]" value="{{ $val('size') }}" min="{{ $min ?? 8 }}"
                max="{{ $max ?? 72 }}">
        </div>
        <div>
            <label for="{{ $uid }}_weight">Ketebalan</label>
            <select class="form-control form-control-sm" id="{{ $uid }}_weight" name="{{ $name }}[weight]">
                @foreach ($weights as $w => $wl)
                    <option value="{{ $w }}" {{ (int) $val('weight') === $w ? 'selected' : '' }}>{{ $wl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="{{ $uid }}_font">Font</label>
            <select class="form-control form-control-sm" id="{{ $uid }}_font" name="{{ $name }}[font]">
                @foreach ($fonts as $f => $fl)
                    <option value="{{ $f }}" {{ $val('font') === $f ? 'selected' : '' }}>{{ $fl }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="sl-checks">
        <label class="sl-check"><input type="checkbox" name="{{ $name }}[italic]" value="1"
                {{ $chk('italic') ? 'checked' : '' }}> Miring</label>
        <label class="sl-check"><input type="checkbox" name="{{ $name }}[uppercase]" value="1"
                {{ $chk('uppercase') ? 'checked' : '' }}> HURUF KAPITAL</label>
    </div>
</div>
