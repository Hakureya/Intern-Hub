{{-- Ruang chat tugas (panel samping) + popup riwayat pengeditan. Dipakai di detail tugas peserta dan mentor/admin. --}}
<div class="wc-side is-open wc-noanim" id="wcSide">
    <button type="button" class="wc-tab" id="wcTab" aria-controls="wcRoot" aria-expanded="false" title="Buka / tutup diskusi">
        <i class="mdi mdi-chat-processing-outline"></i>
        <span>Diskusi</span>
        <em class="wc-badge" id="wcBadge" hidden>0</em>
        <i class="mdi mdi-chevron-left wc-chev"></i>
    </button>

    <aside class="wc wl" id="wcRoot"
        data-index="{{ route('work-logs.chat.index', $task) }}"
        data-store="{{ route('work-logs.chat.store', $task) }}"
        data-update="{{ url('/work-logs/' . $task->id . '/chat') }}"
        data-history="{{ route('work-logs.history', $task) }}"
        data-csrf="{{ csrf_token() }}">
        <div class="wc-inner">
            <header class="wc-head">
                <span class="wc-head-ic"><i class="mdi mdi-chat-processing-outline"></i></span>
                <div>
                    <b>Diskusi tugas</b>
                    <small>Perubahan tugas juga tercatat di sini</small>
                </div>
                <button type="button" class="wc-close" id="wcClose" aria-label="Tutup diskusi"><i class="mdi mdi-chevron-right"></i></button>
            </header>

            <div class="wc-body" id="wcBody">
                <button type="button" class="wc-older" id="wcOlder" hidden>Muat pesan sebelumnya</button>
                <div id="wcList"></div>
                <div class="wc-empty" id="wcEmpty" hidden>Belum ada pesan. Mulai diskusi tentang tugas ini.</div>
            </div>

            <div class="wc-error" id="wcError" hidden></div>
            <div class="wc-tray" id="wcTray" hidden></div>

            <form class="wc-form" id="wcForm" autocomplete="off">
                <button type="button" class="wc-attach" id="wcAttach" aria-label="Lampirkan foto atau file"><i class="mdi mdi-paperclip"></i></button>
                <input type="file" id="wcFiles" multiple hidden
                    accept="image/*,video/mp4,video/webm,video/quicktime,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
                <textarea id="wcInput" rows="1" maxlength="4000" placeholder="Tulis pesan..." aria-label="Tulis pesan"></textarea>
                <button type="submit" class="wc-send" id="wcSend" aria-label="Kirim"><i class="mdi mdi-send"></i></button>
            </form>
        </div>
    </aside>
</div>
<script>
    // Terapkan status buka/tutup tersimpan sebelum halaman digambar (mencegah kedipan). Layar sempit: selalu tertutup.
    (function () {
        try {
            var side = document.getElementById('wcSide');
            var narrow = window.matchMedia('(max-width: 1100px)').matches;
            var saved = localStorage.getItem('wc:open');
            var open = narrow ? false : (saved === null ? true : saved === '1');
            if (!open) { side.classList.remove('is-open'); document.getElementById('wcTab').setAttribute('aria-expanded', 'false'); }
        } catch (e) { /* abaikan */ }
    })();
</script>

{{-- Popup riwayat pengeditan (dibuka tombol dengan atribut data-wc-history) --}}
<div class="wl-modal wl" id="wc-history-modal">
    <div class="wl-modal-box" style="width: min(560px, 100%);">
        <div class="wl-modal-head">
            <h5>Riwayat pengeditan</h5>
            <button type="button" class="wl-modal-x" data-wl-close aria-label="Tutup">&times;</button>
        </div>
        <div class="wl-modal-body">
            <div class="wc-history" id="wcHistoryList"><div class="wc-empty">Memuat...</div></div>
        </div>
    </div>
</div>

{{-- Pratinjau foto layar penuh --}}
<div class="wc-light" id="wcLight" hidden>
    <button type="button" class="wc-light-x" aria-label="Tutup">&times;</button>
    <img alt="">
</div>
