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
                @php($wlLogo = asset('assets/images/logo-plnsc.png'))
                <div class="wl-mates">
                    @if (!empty($people))
                        <div class="wl-mates-list">
                            @foreach (array_slice($people, 0, 6) as $p)
                                <button type="button" class="wl-mate wl-t{{ $p['tone'] ?? 0 }}" aria-label="{{ $p['name'] }}"
                                    data-people="{{ json_encode([$p], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" data-logo="{{ $wlLogo }}">
                                    @if (!empty($p['photo']))
                                        <img src="{{ $p['photo'] }}" alt="">
                                    @else
                                        {{ $p['initials'] }}
                                    @endif
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
                    <b>Diskusi tugas</b>
                </div>
                <button type="button" class="wc-close" id="wcClose" aria-label="Tutup diskusi"><i class="mdi mdi-chevron-right"></i></button>
            </header>

            <div class="wc-body" id="wcBody">
                <button type="button" class="wc-older" id="wcOlder" hidden>Muat pesan sebelumnya</button>
                <div id="wcList"></div>
                <div class="wc-empty" id="wcEmpty" hidden>Belum ada pesan. Mulai diskusi tentang tugas ini.</div>
            </div>

            <div class="wc-error" id="wcError" hidden></div>
            <form class="wc-form" id="wcForm" autocomplete="off">
                <div class="wc-attach-wrap">
                    <button type="button" class="wc-attach" id="wcAttach" aria-label="Lampirkan file" aria-haspopup="true" aria-expanded="false" aria-controls="wcAttachMenu"><i class="mdi mdi-paperclip"></i></button>
                    <div class="wc-attach-menu" id="wcAttachMenu" role="menu" hidden>
                        <button type="button" role="menuitem" data-wc-pick="media">
                            <span class="wc-ic wc-ic--media"><i class="mdi mdi-image-multiple-outline"></i></span> Foto atau video
                        </button>
                        <button type="button" role="menuitem" data-wc-pick="doc">
                            <span class="wc-ic wc-ic--doc"><i class="mdi mdi-file-document-outline"></i></span> Dokumen
                        </button>
                    </div>
                </div>
                <input type="file" id="wcFilesMedia" multiple hidden
                    accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime,.jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov">
                <input type="file" id="wcFilesDoc" multiple hidden
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
                <textarea id="wcInput" rows="1" maxlength="4000" placeholder="Tulis pesan..." aria-label="Tulis pesan"></textarea>
                <button type="submit" class="wc-send" id="wcSend" aria-label="Kirim"><i class="mdi mdi-send"></i></button>
            </form>

            {{-- Pratinjau lampiran + keterangan sebelum dikirim (seperti WhatsApp) --}}
            <div class="wc-compose" id="wcCompose" role="dialog" aria-label="Kirim lampiran" hidden>
                <header class="wc-compose-head">
                    <button type="button" class="wc-compose-x" id="wcComposeClose" aria-label="Batalkan"><i class="mdi mdi-close"></i></button>
                    <div><b id="wcComposeTitle">Kirim lampiran</b><small id="wcComposeSub"></small></div>
                </header>
                <div class="wc-compose-stage" id="wcStage"></div>
                <div class="wc-compose-error" id="wcComposeError" hidden></div>
                <div class="wc-compose-strip" id="wcStrip"></div>
                <div class="wc-compose-foot">
                    <textarea id="wcCaption" rows="1" maxlength="4000" placeholder="Tambahkan keterangan..." aria-label="Keterangan lampiran"></textarea>
                    <button type="button" class="wc-send" id="wcComposeSend" aria-label="Kirim lampiran"><i class="mdi mdi-send"></i><em id="wcComposeCount" hidden></em></button>
                </div>
            </div>
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
