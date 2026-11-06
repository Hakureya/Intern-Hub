<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Slide "Tentang Kami" di landing page.
 * Kolom `card` dan `copy` berisi JSON; nilai yang belum diisi memakai default di bawah.
 */
class LandingSlide extends Model
{
    public const POSITIONS = [
        'top-left', 'top-center', 'top-right',
        'middle-left', 'middle-center', 'middle-right',
        'bottom-left', 'bottom-center', 'bottom-right',
    ];

    public const IMAGE_POSITIONS = ['center', 'top', 'bottom', 'left', 'right'];
    public const IMAGE_FITS = ['cover', 'contain'];
    public const WEIGHTS = [400, 500, 600, 700, 800];
    public const FONTS = ['display' => 'var(--cs-display)', 'body' => 'var(--cs-font)', 'serif' => 'Georgia, serif'];

    protected $table = 'landing_slides';

    protected $fillable = [
        'sort_order', 'is_active', 'image_path', 'image_alt', 'image_position',
        'image_fit', 'bg_color', 'card', 'copy',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'card' => 'array',
        'copy' => 'array',
    ];

    /* ------------------------------------------------------------------ */
    /* Default                                                             */
    /* ------------------------------------------------------------------ */

    private static function style(string $color, int $size, int $weight, string $font, bool $uppercase = false): array
    {
        return [
            'text' => '', 'color' => $color, 'size' => $size, 'weight' => $weight,
            'italic' => false, 'uppercase' => $uppercase, 'font' => $font,
        ];
    }

    public static function defaultCard(): array
    {
        return [
            'enabled' => true,
            'position' => 'bottom-center',
            'width' => 90,
            'bg_color' => '#fffdf7',
            'bg_opacity' => 96,
            'quote_mark' => false,
            'quote_color' => '#5a9cc8',
            'label' => self::style('#778996', 9, 800, 'body', true),
            'title' => self::style('#0f2a43', 16, 700, 'display'),
            'subtitle' => self::style('#777777', 11, 400, 'body'),
        ];
    }

    public static function defaultCopy(): array
    {
        return [
            'align' => 'left',
            'eyebrow' => self::style('#7b8a99', 10, 800, 'body', true),
            'title' => self::style('#0f2a43', 44, 800, 'display') + ['accent_color' => '#1b6aa8'],
            'body' => self::style('#647582', 14, 400, 'body'),
            'items' => [],
            'items_style' => ['number_color' => '#4f93c4', 'title_color' => '#0f2a43', 'text_color' => '#6d808e'],
            'person' => [
                'enabled' => false, 'name' => '', 'subtitle' => '',
                'name_color' => '#0f2a43', 'subtitle_color' => '#798b97',
            ],
        ];
    }

    /** Batas ukuran font per elemen: [min, max]. */
    private const SIZE_LIMITS = [
        'card.label' => [8, 24], 'card.title' => [10, 48], 'card.subtitle' => [8, 28],
        'copy.eyebrow' => [8, 24], 'copy.title' => [20, 72], 'copy.body' => [11, 24],
    ];

    /* ------------------------------------------------------------------ */
    /* Accessor                                                            */
    /* ------------------------------------------------------------------ */

    public function getCardDataAttribute(): array
    {
        return array_replace_recursive(self::defaultCard(), $this->card ?: []);
    }

    public function getCopyDataAttribute(): array
    {
        $saved = $this->copy ?: [];
        $data = array_replace_recursive(self::defaultCopy(), $saved);
        // daftar poin tidak digabung per indeks dengan default
        $data['items'] = array_values($saved['items'] ?? []);

        return $data;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (Str::startsWith($this->image_path, ['images/', 'http://', 'https://'])) {
            return Str::startsWith($this->image_path, 'images/') ? asset($this->image_path) : $this->image_path;
        }

        return asset('storage/' . $this->image_path);
    }

    /** True jika gambar adalah berkas unggahan (di disk public), bukan aset bawaan. */
    public function hasUploadedImage(): bool
    {
        return $this->image_path && ! Str::startsWith($this->image_path, ['images/', 'http://', 'https://']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Slide aktif untuk landing; kosong jika tabel belum dimigrasi. */
    public static function forLanding(): Collection
    {
        try {
            if (! Schema::hasTable('landing_slides')) {
                return collect();
            }

            return static::active()->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Sanitasi input dari form (whitelist + batas nilai)                  */
    /* ------------------------------------------------------------------ */

    public static function sanitizeColor($value, string $default): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }

    private static function sanitizeInt($value, int $min, int $max, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }

    private static function sanitizeBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function sanitizeText($value, int $max): string
    {
        $value = is_string($value) ? str_replace(["\r\n", "\r"], "\n", $value) : '';

        return mb_substr(trim($value), 0, $max);
    }

    private static function sanitizeStyle($input, array $default, string $limitKey, int $maxText): array
    {
        $input = is_array($input) ? $input : [];
        [$minSize, $maxSize] = self::SIZE_LIMITS[$limitKey];

        return [
            'text' => self::sanitizeText($input['text'] ?? '', $maxText),
            'color' => self::sanitizeColor($input['color'] ?? null, $default['color']),
            'size' => self::sanitizeInt($input['size'] ?? null, $minSize, $maxSize, $default['size']),
            'weight' => in_array((int) ($input['weight'] ?? 0), self::WEIGHTS, true) ? (int) $input['weight'] : $default['weight'],
            'italic' => self::sanitizeBool($input['italic'] ?? false),
            'uppercase' => self::sanitizeBool($input['uppercase'] ?? false),
            'font' => is_string($input['font'] ?? null) && array_key_exists($input['font'], self::FONTS) ? $input['font'] : $default['font'],
        ];
    }

    public static function sanitizeCard($input): array
    {
        $d = self::defaultCard();
        $input = is_array($input) ? $input : [];

        return [
            'enabled' => self::sanitizeBool($input['enabled'] ?? false),
            'position' => in_array($input['position'] ?? '', self::POSITIONS, true) ? $input['position'] : $d['position'],
            'width' => self::sanitizeInt($input['width'] ?? null, 30, 100, $d['width']),
            'bg_color' => self::sanitizeColor($input['bg_color'] ?? null, $d['bg_color']),
            'bg_opacity' => self::sanitizeInt($input['bg_opacity'] ?? null, 0, 100, $d['bg_opacity']),
            'quote_mark' => self::sanitizeBool($input['quote_mark'] ?? false),
            'quote_color' => self::sanitizeColor($input['quote_color'] ?? null, $d['quote_color']),
            'label' => self::sanitizeStyle($input['label'] ?? [], $d['label'], 'card.label', 120),
            'title' => self::sanitizeStyle($input['title'] ?? [], $d['title'], 'card.title', 400),
            'subtitle' => self::sanitizeStyle($input['subtitle'] ?? [], $d['subtitle'], 'card.subtitle', 200),
        ];
    }

    public static function sanitizeCopy($input): array
    {
        $d = self::defaultCopy();
        $input = is_array($input) ? $input : [];

        $title = self::sanitizeStyle($input['title'] ?? [], $d['title'], 'copy.title', 300);
        $titleInput = is_array($input['title'] ?? null) ? $input['title'] : [];
        $title['accent_color'] = self::sanitizeColor($titleInput['accent_color'] ?? null, $d['title']['accent_color']);

        $items = [];
        foreach (array_slice(array_values(is_array($input['items'] ?? null) ? $input['items'] : []), 0, 8) as $item) {
            $itemTitle = self::sanitizeText($item['title'] ?? '', 120);
            $itemText = self::sanitizeText($item['text'] ?? '', 300);
            if ($itemTitle !== '' || $itemText !== '') {
                $items[] = ['title' => $itemTitle, 'text' => $itemText];
            }
        }

        $itemsStyle = is_array($input['items_style'] ?? null) ? $input['items_style'] : [];
        $person = is_array($input['person'] ?? null) ? $input['person'] : [];

        return [
            'align' => in_array($input['align'] ?? '', ['left', 'center', 'right'], true) ? $input['align'] : 'left',
            'eyebrow' => self::sanitizeStyle($input['eyebrow'] ?? [], $d['eyebrow'], 'copy.eyebrow', 120),
            'title' => $title,
            'body' => self::sanitizeStyle($input['body'] ?? [], $d['body'], 'copy.body', 3000),
            'items' => $items,
            'items_style' => [
                'number_color' => self::sanitizeColor($itemsStyle['number_color'] ?? null, $d['items_style']['number_color']),
                'title_color' => self::sanitizeColor($itemsStyle['title_color'] ?? null, $d['items_style']['title_color']),
                'text_color' => self::sanitizeColor($itemsStyle['text_color'] ?? null, $d['items_style']['text_color']),
            ],
            'person' => [
                'enabled' => self::sanitizeBool($person['enabled'] ?? false),
                'name' => self::sanitizeText($person['name'] ?? '', 120),
                'subtitle' => self::sanitizeText($person['subtitle'] ?? '', 160),
                'name_color' => self::sanitizeColor($person['name_color'] ?? null, $d['person']['name_color']),
                'subtitle_color' => self::sanitizeColor($person['subtitle_color'] ?? null, $d['person']['subtitle_color']),
            ],
        ];
    }

    public static function sanitizeBasics(array $input): array
    {
        return [
            'image_alt' => self::sanitizeText($input['image_alt'] ?? '', 200) ?: null,
            'image_position' => in_array($input['image_position'] ?? '', self::IMAGE_POSITIONS, true) ? $input['image_position'] : 'center',
            'image_fit' => in_array($input['image_fit'] ?? '', self::IMAGE_FITS, true) ? $input['image_fit'] : 'cover',
            'bg_color' => self::sanitizeColor($input['bg_color'] ?? null, '#bcdcee'),
            'is_active' => self::sanitizeBool($input['is_active'] ?? false),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Helper tampilan (dipakai partial landing)                           */
    /* ------------------------------------------------------------------ */

    /** CSS inline untuk satu elemen teks. */
    public static function textCss(array $el): string
    {
        return sprintf(
            'color:%s;font-size:%dpx;font-weight:%d;font-style:%s;text-transform:%s;font-family:%s;',
            $el['color'],
            $el['size'],
            $el['weight'],
            $el['italic'] ? 'italic' : 'normal',
            $el['uppercase'] ? 'uppercase' : 'none',
            self::FONTS[$el['font']] ?? self::FONTS['body']
        );
    }

    /** CSS posisi kartu di atas gambar. */
    public static function cardPositionCss(array $card): string
    {
        [$v, $h] = explode('-', $card['position']);

        $css = 'width:' . (int) $card['width'] . '%;min-width:min(240px,90%);';
        $css .= $h === 'left' ? 'left:5%;right:auto;' : ($h === 'right' ? 'left:auto;right:5%;' : 'left:50%;right:auto;');
        $css .= $v === 'top' ? 'top:5%;bottom:auto;' : ($v === 'bottom' ? 'top:auto;bottom:5%;' : 'top:50%;bottom:auto;');

        $tx = $h === 'center' ? '-50%' : '0';
        $ty = $v === 'middle' ? '-50%' : '0';
        if ($tx !== '0' || $ty !== '0') {
            $css .= "transform:translate({$tx},{$ty});";
        }

        return $css;
    }

    public static function cardBackground(array $card): string
    {
        $hex = ltrim($card['bg_color'], '#');
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        return sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, round($card['bg_opacity'] / 100, 2));
    }

    /** Judul: baris baru jadi <br>, teks di antara *...* diberi warna aksen. Aman dari XSS (di-escape dulu). */
    public static function formatTitle(string $text, string $accentColor): string
    {
        $html = e($text);
        $html = preg_replace(
            '/\*(.+?)\*/s',
            '<span class="cs-accent" style="color:' . $accentColor . '">$1</span>',
            $html
        );

        return nl2br($html, false);
    }

    /** Pecah teks menjadi paragraf. */
    public static function paragraphs(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n|\n/', $text))));
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $letters = '';
        foreach (array_slice(array_filter($parts), 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters;
    }
}
