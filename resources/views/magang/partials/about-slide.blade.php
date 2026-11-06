{{-- Satu slide "Tentang Kami". Struktur HTML ini dicerminkan oleh pratinjau di admin (admin/slides/form.blade.php). --}}
@php
    use App\Models\LandingSlide;

    $card = $slide->card_data;
    $copy = $slide->copy_data;
    $imageUrl = $slide->image_url;
    $hasCardText = $card['label']['text'] !== '' || $card['title']['text'] !== '' || $card['subtitle']['text'] !== '';
    $justify = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$copy['align']];
    $pMargin = ['left' => '0 0 12px', 'center' => '0 auto 12px', 'right' => '0 0 12px auto'][$copy['align']];
    $titleCss = LandingSlide::textCss($copy['title'])
        . 'letter-spacing:-.045em;font-size:clamp(' . round($copy['title']['size'] * 0.68) . 'px,'
        . round($copy['title']['size'] / 11, 2) . 'vw,' . $copy['title']['size'] . 'px);';
@endphp
<div class="cs-slide{{ $index === 0 ? ' is-active' : '' }}" role="group" aria-roledescription="slide"
    aria-label="{{ $index + 1 }} dari {{ $total }}">
    <div class="cs-why-visual" style="background:{{ $slide->bg_color }};">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $slide->image_alt }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                style="object-fit:{{ $slide->image_fit }};object-position:{{ $slide->image_position }};"
                onerror="this.remove()">
        @endif

        @if ($card['enabled'] && $hasCardText)
            <div class="cs-photo-card{{ $card['quote_mark'] ? ' is-quote' : '' }}"
                style="{{ LandingSlide::cardPositionCss($card) }}background:{{ LandingSlide::cardBackground($card) }};">
                @if ($card['quote_mark'])
                    <span class="cs-quote-mark"
                        style="font:800 34px/0.6 Georgia,serif;letter-spacing:normal;text-transform:none;color:{{ $card['quote_color'] }};">“</span>
                @endif
                @if ($card['label']['text'] !== '')
                    <span style="{{ LandingSlide::textCss($card['label']) }}">{{ $card['label']['text'] }}</span>
                @endif
                @if ($card['title']['text'] !== '')
                    <strong style="{{ LandingSlide::textCss($card['title']) }}">{{ $card['title']['text'] }}</strong>
                @endif
                @if ($card['subtitle']['text'] !== '')
                    <small style="{{ LandingSlide::textCss($card['subtitle']) }}">{{ $card['subtitle']['text'] }}</small>
                @endif
            </div>
        @endif
    </div>

    <div class="cs-why-copy" style="text-align:{{ $copy['align'] }};">
        @if ($copy['eyebrow']['text'] !== '')
            <div class="cs-eyebrow" style="{{ LandingSlide::textCss($copy['eyebrow']) }}">{{ $copy['eyebrow']['text'] }}</div>
        @endif

        @if ($copy['title']['text'] !== '')
            <h2 style="{{ $titleCss }}">{!! LandingSlide::formatTitle($copy['title']['text'], $copy['title']['accent_color']) !!}</h2>
        @endif

        @foreach (LandingSlide::paragraphs($copy['body']['text']) as $paragraph)
            <p style="{{ LandingSlide::textCss($copy['body']) }}margin:{{ $pMargin }};">{{ $paragraph }}</p>
        @endforeach

        @foreach ($copy['items'] as $n => $item)
            <div class="cs-benefit" style="justify-content:{{ $justify }};">
                <span style="color:{{ $copy['items_style']['number_color'] }};">{{ sprintf('%02d', $n + 1) }}</span>
                <div>
                    <strong style="color:{{ $copy['items_style']['title_color'] }};">{{ $item['title'] }}</strong>
                    <p style="color:{{ $copy['items_style']['text_color'] }};">{{ $item['text'] }}</p>
                </div>
            </div>
        @endforeach

        @if ($copy['person']['enabled'] && $copy['person']['name'] !== '')
            <div class="cs-person" style="justify-content:{{ $justify }};">
                <div class="cs-avatar">{{ LandingSlide::initials($copy['person']['name']) }}</div>
                <div>
                    <strong style="color:{{ $copy['person']['name_color'] }};">{{ $copy['person']['name'] }}</strong>
                    @if ($copy['person']['subtitle'] !== '')
                        <small style="color:{{ $copy['person']['subtitle_color'] }};">{{ $copy['person']['subtitle'] }}</small>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
