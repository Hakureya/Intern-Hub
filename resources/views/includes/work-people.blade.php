{{-- Footer kartu: "Rekan satu projek" (foto profil, tooltip, kartu putar) atau "Dikerjakan sendiri". Param: $people --}}
@php($wlLogo = asset('assets/images/logo-plnsc.png'))
<div class="wl-mates">
    @if (!empty($people))
        <span class="wl-mates-label">Rekan satu projek:</span>
        <div class="wl-mates-list">
            @foreach (array_slice($people, 0, 6) as $p)
                <button type="button" class="wl-mate wl-t{{ $p['tone'] ?? 0 }}" aria-label="{{ $p['name'] }}"
                    data-people="{{ json_encode([$p], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" data-logo="{{ $wlLogo }}">
                    @if (!empty($p['photo']))
                        <img src="{{ $p['photo'] }}" alt="">
                    @else
                        {{ $p['initials'] }}
                    @endif
                    <span class="wl-tip"><span class="wl-tip-line"><b>{{ $p['name'] }}</b>@if (!empty($p['division']))<em>{{ $p['division'] }}</em>@endif</span></span>
                </button>
            @endforeach
            @if (count($people) > 6)
                <button type="button" class="wl-mate wl-mate--more" aria-label="Rekan lainnya"
                    data-people="{{ json_encode(array_slice($people, 6), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" data-logo="{{ $wlLogo }}">
                    +{{ count($people) - 6 }}
                </button>
            @endif
        </div>
    @else
        <span class="wl-mates-label wl-mates-label--solo"><i class="mdi mdi-account-outline"></i> Dikerjakan sendiri</span>
    @endif
</div>
