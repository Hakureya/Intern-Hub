@include('includes.dialog')

@if (session()->has('flash_message'))
    <script>
        CSDialog.flash(
            @json(session('flash_message.level'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            @json(session('flash_message.title'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            @json(session('flash_message.message'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        );
    </script>
@endif

@if (session()->has('flash_message_overlay'))
    <script>
        CSDialog.flash(
            @json(session('flash_message_overlay.level'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            @json(session('flash_message_overlay.title'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            @json(session('flash_message_overlay.message'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            true
        );
    </script>
@endif
