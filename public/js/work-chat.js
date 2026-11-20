/* Ruang chat tugas: pesan, edit pesan ("diedit"), lampiran (menu klip: foto/video atau dokumen, banyak file, pratinjau + keterangan sebelum kirim seperti WhatsApp). Riwayat pengeditan tugas hanya tampil di popup Riwayat. */
(function () {
    'use strict';

    var root = document.getElementById('wcRoot');
    if (!root) { return; }

    var URLS = {
        index: root.getAttribute('data-index'),
        store: root.getAttribute('data-store'),
        update: root.getAttribute('data-update'),
        history: root.getAttribute('data-history')
    };
    var CSRF = root.getAttribute('data-csrf');
    var MAX_FILES = 10;
    var MAX_SIZE = 25 * 1024 * 1024;

    var body = document.getElementById('wcBody');
    var list = document.getElementById('wcList');
    var olderBtn = document.getElementById('wcOlder');
    var emptyEl = document.getElementById('wcEmpty');
    var errorBox = document.getElementById('wcError');
    var form = document.getElementById('wcForm');
    var input = document.getElementById('wcInput');
    var sendBtn = document.getElementById('wcSend');
    var attachBtn = document.getElementById('wcAttach');
    var attachMenu = document.getElementById('wcAttachMenu');
    var filesMedia = document.getElementById('wcFilesMedia');
    var filesDoc = document.getElementById('wcFilesDoc');
    var composeEl = document.getElementById('wcCompose');
    var composeTitle = document.getElementById('wcComposeTitle');
    var composeSub = document.getElementById('wcComposeSub');
    var composeErr = document.getElementById('wcComposeError');
    var composeSend = document.getElementById('wcComposeSend');
    var composeCount = document.getElementById('wcComposeCount');
    var composeClose = document.getElementById('wcComposeClose');
    var stage = document.getElementById('wcStage');
    var strip = document.getElementById('wcStrip');
    var caption = document.getElementById('wcCaption');
    var light = document.getElementById('wcLight');
    var side = document.getElementById('wcSide');
    var tab = document.getElementById('wcTab');
    var badge = document.getElementById('wcBadge');
    var closeBtn = document.getElementById('wcClose');
    var unread = 0;       // pesan baru dari orang lain saat tab tertutup

    var rows = {};        // id -> elemen baris
    var lastId = 0;       // id pesan terbaru yang sudah tampil
    var oldestId = null;  // id pesan tertua yang sudah tampil
    var since = null;     // waktu server pada polling terakhir (untuk mendeteksi pesan yang diedit)
    var pending = [];     // lampiran yang dipilih, belum terkirim: { file, kind: image|video|file, url }
    var sending = false;
    var errTimer = null;

    /* ---------- util ---------- */
    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function linkify(text) {
        return esc(text).replace(/(https?:\/\/[^\s<]+)/g, function (u) {
            var tail = '';
            var m = /[.,;:!?)]+$/.exec(u);
            if (m) { tail = m[0]; u = u.slice(0, -tail.length); }
            return '<a href="' + u + '" target="_blank" rel="noopener noreferrer">' + u + '</a>' + tail;
        });
    }
    function sizeText(b) {
        return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    }
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function dayKey(iso) { var d = new Date(iso); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function dayLabel(iso) {
        var d = new Date(iso);
        var now = new Date();
        var key = dayKey(iso);
        if (key === dayKey(now.toISOString())) { return 'Hari ini'; }
        var y = new Date(now.getTime() - 86400000);
        if (key === dayKey(y.toISOString())) { return 'Kemarin'; }
        return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }
    function timeText(iso) { return new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); }
    function fullTime(iso) {
        return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) + ', ' + timeText(iso);
    }
    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.hidden = false;
        clearTimeout(errTimer);
        errTimer = setTimeout(function () { errorBox.hidden = true; }, 6000);
    }
    function headers(extra) {
        var h = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF };
        for (var k in extra) { h[k] = extra[k]; }
        return h;
    }
    function nearBottom() { return body.scrollHeight - body.scrollTop - body.clientHeight < 90; }
    function toBottom() { body.scrollTop = body.scrollHeight; }
    function errorFrom(res, json) {
        if (json && json.errors) {
            var first = Object.keys(json.errors)[0];
            return json.errors[first][0];
        }
        return (json && json.message) || ('Gagal (' + res.status + ')');
    }

    /* ---------- tab samping: buka / tutup ---------- */
    function isOpen() { return side.classList.contains('is-open'); }
    function isNarrow() { return window.matchMedia('(max-width: 1100px)').matches; }
    function updateBadge() {
        badge.hidden = unread < 1;
        badge.textContent = unread > 99 ? '99+' : unread;
    }
    function setOpen(open) {
        side.classList.toggle('is-open', open);
        tab.setAttribute('aria-expanded', open ? 'true' : 'false');
        // HP: panel memenuhi layar, jadi halaman di belakangnya tidak boleh ikut bergulir
        document.documentElement.classList.toggle('wc-lock', open && window.matchMedia('(max-width: 640px)').matches);
        if (!isNarrow()) { try { localStorage.setItem('wc:open', open ? '1' : '0'); } catch (e) { /* abaikan */ } }
        if (open) {
            unread = 0;
            updateBadge();
            setTimeout(function () { toBottom(); }, 300); // setelah animasi lebar selesai
        }
    }
    tab.addEventListener('click', function () { setOpen(!isOpen()); });
    closeBtn.addEventListener('click', function () { setOpen(false); });
    // aktifkan animasi hanya setelah status awal (dari skrip kecil di partial) diterapkan
    requestAnimationFrame(function () { requestAnimationFrame(function () { side.classList.remove('wc-noanim'); }); });

    /* ---------- render ---------- */
    function filesHtml(files) {
        if (!files || !files.length) { return ''; }
        var imgs = files.filter(function (f) { return f.type === 'image'; });
        var vids = files.filter(function (f) { return f.type === 'video'; });
        var others = files.filter(function (f) { return f.type === 'file'; });
        var html = '';
        if (imgs.length) {
            html += '<div class="wc-media' + (imgs.length > 1 ? ' two' : '') + '">' + imgs.map(function (f) {
                return '<a class="wc-img" href="' + esc(f.url) + '" data-wc-img><img loading="lazy" src="' + esc(f.url) + '" alt="' + esc(f.name) + '"></a>';
            }).join('') + '</div>';
        }
        vids.forEach(function (f) {
            html += '<div class="wc-media"><video class="wc-video" controls preload="metadata" playsinline src="' + esc(f.url) + '#t=0.1"></video></div>';
        });
        others.forEach(function (f) {
            html += '<a class="wc-file" href="' + esc(f.download_url) + '"><i class="mdi mdi-file-document-outline"></i>' +
                '<span><b title="' + esc(f.name) + '">' + esc(f.name) + '</b><small>' + sizeText(f.size) + ' &middot; Unduh</small></span></a>';
        });
        return html;
    }

    function roleBadge(u) { return '<span class="wc-role ' + esc(u.role) + '">' + (u.role === 'mentor' ? 'Mentor' : 'Peserta') + '</span>'; }

    function sysHtml(m, withDate) {
        var items = (m.changes || []).map(function (c) {
            if (c.note) { return '<li><b>' + esc(c.label) + ':</b> ' + esc(c.note) + '</li>'; }
            return '<li><b>' + esc(c.label) + ':</b> <s>' + esc(c.old) + '</s><span class="arrow">&rarr;</span><span class="new">' + esc(c.new) + '</span></li>';
        }).join('');
        return '<div class="wc-sys"><div class="wc-sys-head"><i class="mdi mdi-pencil-outline"></i>' +
            '<span><b>' + esc(m.user.name) + '</b> ' + roleBadge(m.user) + ' mengubah tugas</span>' +
            '<time>' + esc(withDate ? fullTime(m.created_at) : timeText(m.created_at)) + '</time></div><ul>' + items + '</ul></div>';
    }

    function textHtml(m) { return m.body ? linkify(m.body) : ''; }

    function bubbleHtml(m) {
        var mine = m.user.mine;
        return '<div class="wc-bubble">' +
            (mine ? '' : '<div class="wc-who">' + esc(m.user.name) + roleBadge(m.user) + '</div>') +
            filesHtml(m.files) +
            '<div class="wc-text">' + textHtml(m) + '</div>' +
            '<div class="wc-meta">' +
            (mine ? '<button type="button" class="wc-act" data-wc-edit aria-label="Edit pesan"><i class="mdi mdi-pencil-outline"></i> Edit</button>' : '') +
            '<span class="wc-edited">' + (m.edited ? 'diedit' : '') + '</span>' +
            '<span>' + esc(timeText(m.created_at)) + '</span></div></div>';
    }

    function makeRow(m) {
        var el = document.createElement('div');
        el.setAttribute('data-id', m.id);
        el.className = 'wc-row' + (m.user.mine ? ' mine' : '');
        el.innerHTML = bubbleHtml(m);
        el._m = m;
        return el;
    }

    function dayEl(iso) {
        var el = document.createElement('div');
        el.className = 'wc-day';
        el.setAttribute('data-day', dayKey(iso));
        el.innerHTML = '<span>' + esc(dayLabel(iso)) + '</span>';
        return el;
    }

    // Perbarui pesan yang sudah ada (teks + label "diedit") tanpa menyentuh foto/video agar tidak termuat ulang.
    function updateRow(el, m) {
        el._m = m;
        var t = el.querySelector('.wc-text');
        var e = el.querySelector('.wc-edited');
        if (t && !t.querySelector('textarea')) { t.innerHTML = textHtml(m); }
        if (e) { e.textContent = m.edited ? 'diedit' : ''; }
    }

    function lastDayShown() {
        var d = list.querySelectorAll('.wc-day');
        return d.length ? d[d.length - 1].getAttribute('data-day') : null;
    }

    function appendMessages(arr) {
        var added = false;
        arr.forEach(function (m) {
            if (rows[m.id]) { updateRow(rows[m.id], m); return; }
            if (lastDayShown() !== dayKey(m.created_at)) { list.appendChild(dayEl(m.created_at)); }
            var el = makeRow(m);
            rows[m.id] = el;
            list.appendChild(el);
            if (m.id > lastId) { lastId = m.id; }
            if (oldestId === null || m.id < oldestId) { oldestId = m.id; }
            added = true;
        });
        emptyEl.hidden = Object.keys(rows).length > 0;
        return added;
    }

    function prependMessages(arr) {
        var frag = document.createDocumentFragment();
        var prevDay = null;
        arr.forEach(function (m) {
            if (rows[m.id]) { return; }
            if (prevDay !== dayKey(m.created_at)) { frag.appendChild(dayEl(m.created_at)); prevDay = dayKey(m.created_at); }
            var el = makeRow(m);
            rows[m.id] = el;
            frag.appendChild(el);
            if (oldestId === null || m.id < oldestId) { oldestId = m.id; }
        });
        // pemisah hari ganda di sambungan: hapus yang lama bila harinya sama
        var firstOld = list.firstElementChild;
        if (prevDay && firstOld && firstOld.classList.contains('wc-day') && firstOld.getAttribute('data-day') === prevDay) {
            list.removeChild(firstOld);
        }
        list.insertBefore(frag, list.firstChild);
    }

    /* ---------- pengambilan data ---------- */
    function fetchJson(url) {
        return fetch(url, { credentials: 'same-origin', headers: headers({}) }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    function loadInitial() {
        fetchJson(URLS.index).then(function (res) {
            appendMessages(res.messages);
            since = res.server_time;
            olderBtn.hidden = !res.has_more;
            emptyEl.hidden = Object.keys(rows).length > 0;
            toBottom();
        }).catch(function () { showError('Gagal memuat pesan.'); });
    }

    olderBtn.addEventListener('click', function () {
        if (oldestId === null) { return; }
        var before = body.scrollHeight;
        fetchJson(URLS.index + '?before=' + oldestId).then(function (res) {
            prependMessages(res.messages);
            olderBtn.hidden = !res.has_more;
            body.scrollTop += body.scrollHeight - before;
        }).catch(function () { showError('Gagal memuat pesan sebelumnya.'); });
    });

    var polling = false;
    function poll() {
        if (polling || document.hidden || since === null) { return; }
        polling = true;
        var stick = nearBottom();
        fetchJson(URLS.index + '?after=' + lastId + '&since=' + encodeURIComponent(since)).then(function (res) {
            since = res.server_time;
            var fresh = res.messages.filter(function (m) { return !rows[m.id] && !m.user.mine; }).length;
            if (fresh && !isOpen()) { unread += fresh; updateBadge(); }
            if (appendMessages(res.messages) && stick) { toBottom(); }
        }).catch(function () { /* diam: coba lagi pada siklus berikutnya */ }).then(function () { polling = false; });
    }
    setInterval(poll, 5000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });

    /* ---------- kirim pesan (satu endpoint untuk teks saja maupun lampiran + keterangan) ---------- */
    function autosizeEl(el, max) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, max) + 'px'; }
    function autosize() { autosizeEl(input, 120); }

    function postMessage(fd) {
        return fetch(URLS.store, { method: 'POST', credentials: 'same-origin', headers: headers({}), body: fd }).then(function (r) {
            return r.json().catch(function () { return null; }).then(function (json) { return { r: r, json: json }; });
        });
    }

    input.addEventListener('input', autosize);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : sendBtn.click(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (sending) { return; }
        var text = input.value.trim();
        if (!text) { return; }

        var fd = new FormData();
        fd.append('body', text);

        sending = true;
        sendBtn.disabled = true;
        postMessage(fd).then(function (x) {
            if (!x.r.ok) { showError(errorFrom(x.r, x.json)); return; }
            appendMessages([x.json.message]);
            input.value = '';
            autosize();
            toBottom();
        }).catch(function () { showError('Pesan gagal terkirim. Periksa koneksi lalu coba lagi.'); })
            .then(function () { sending = false; sendBtn.disabled = false; input.focus(); });
    });

    /* ---------- lampiran: menu klip kertas ---------- */
    var ALLOWED = /\.(jpe?g|png|gif|webp|mp4|webm|mov|pdf|docx?|xlsx?|pptx?|txt|csv|zip)$/i;
    var pickMode = 'media';   // mode terakhir ('media' | 'doc'), dipakai tombol "+" di pratinjau
    var selected = 0;         // indeks lampiran yang sedang dipratinjau

    function setMenu(open) {
        attachMenu.hidden = !open;
        attachBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function pickFiles(mode) {
        pickMode = mode === 'doc' ? 'doc' : 'media';
        setMenu(false);
        (pickMode === 'doc' ? filesDoc : filesMedia).click();
    }

    attachBtn.addEventListener('click', function (e) { e.stopPropagation(); setMenu(attachMenu.hidden); });
    attachMenu.addEventListener('click', function (e) {
        var b = e.target.closest('[data-wc-pick]');
        if (b) { pickFiles(b.getAttribute('data-wc-pick')); }
    });
    document.addEventListener('click', function (e) {
        if (!attachMenu.hidden && !e.target.closest('.wc-attach-wrap')) { setMenu(false); }
    });
    [filesMedia, filesDoc].forEach(function (inp) {
        inp.addEventListener('change', function () { addFiles(inp.files); inp.value = ''; });
    });
    input.addEventListener('paste', function (e) {
        var files = e.clipboardData && e.clipboardData.files;
        if (files && files.length) { e.preventDefault(); pickMode = 'media'; addFiles(files); }
    });

    /* ---------- lampiran: pratinjau + keterangan sebelum kirim ---------- */
    function kindOf(f) {
        if (/^image\/(jpeg|png|gif|webp)$/.test(f.type)) { return 'image'; }
        if (f.type.indexOf('video/') === 0) { return 'video'; }
        return 'file';
    }
    function extOf(name) { var m = /\.([a-z0-9]+)$/i.exec(name); return m ? m[1].toUpperCase() : 'FILE'; }
    function composeOpen() { return !composeEl.hidden; }

    function flashError(msg) {
        if (!composeOpen()) { showError(msg); return; }
        composeErr.textContent = msg;
        composeErr.hidden = false;
        clearTimeout(errTimer);
        errTimer = setTimeout(function () { composeErr.hidden = true; }, 6000);
    }

    function addFiles(fileList) {
        var before = pending.length;
        var errors = [];
        [].slice.call(fileList).forEach(function (f) {
            if (pending.length >= MAX_FILES) { errors.push('Maksimal ' + MAX_FILES + ' file per pesan.'); return; }
            if (!ALLOWED.test(f.name)) { errors.push(f.name + ': format tidak didukung.'); return; }
            if (f.size > MAX_SIZE) { errors.push(f.name + ': ukuran maksimal 25 MB.'); return; }
            var dup = pending.some(function (x) { var o = x.file; return o.name === f.name && o.size === f.size && o.lastModified === f.lastModified; });
            if (dup) { return; }
            var kind = kindOf(f);
            pending.push({ file: f, kind: kind, url: kind === 'file' ? null : URL.createObjectURL(f) });
        });

        if (!pending.length) { if (errors.length) { showError(errors[0]); } return; }

        if (pending.length > before) { selected = before; }   // tampilkan lampiran baru pertama
        pickMode = pending[0].kind === 'file' ? 'doc' : 'media';
        if (!composeOpen()) { openCompose(); }
        renderCompose(pending.length > before);
        if (errors.length) { flashError(errors[0]); }
    }

    function openCompose() {
        caption.value = input.value;   // teks yang sudah diketik di kolom pesan jadi keterangan awal
        composeErr.hidden = true;
        composeEl.hidden = false;
        autosizeEl(caption, 110);      // setelah tampil: saat tersembunyi scrollHeight = 0 sehingga kolom jadi gepeng
        if (!window.matchMedia('(pointer: coarse)').matches) { setTimeout(function () { caption.focus(); }, 30); }
    }

    function closeCompose() {
        if (sending) { return; }
        pending.forEach(function (p) { if (p.url) { URL.revokeObjectURL(p.url); } });
        pending = [];
        selected = 0;
        stage.innerHTML = '';
        strip.innerHTML = '';
        composeEl.hidden = true;
        composeErr.hidden = true;
    }

    function stageEl(cur) {
        var el;
        if (cur.kind === 'image') {
            el = document.createElement('img');
            el.src = cur.url;
            el.alt = cur.file.name;
        } else if (cur.kind === 'video') {
            el = document.createElement('video');
            el.src = cur.url;
            el.controls = true;
            el.preload = 'metadata';
            el.setAttribute('playsinline', '');
        } else {
            el = document.createElement('div');
            el.className = 'wc-doc';
            el.innerHTML = '<i class="mdi ' + (/\.pdf$/i.test(cur.file.name) ? 'mdi-file-pdf-box' : 'mdi-file-document-outline') + '"></i><b></b><small></small>';
            el.querySelector('b').textContent = cur.file.name;
            el.querySelector('small').textContent = sizeText(cur.file.size) + ' \u00b7 ' + extOf(cur.file.name);
        }
        return el;
    }

    function thumbEl(p, i) {
        var th = document.createElement('div');
        th.className = 'wc-th' + (i === selected ? ' is-active' : '');
        th.setAttribute('data-i', i);
        th.setAttribute('role', 'button');
        th.setAttribute('tabindex', '0');
        th.setAttribute('aria-label', 'Pratinjau ' + p.file.name);
        if (p.kind === 'image') {
            var img = document.createElement('img');
            img.src = p.url;
            img.alt = '';
            th.appendChild(img);
        } else if (p.kind === 'video') {
            th.innerHTML = '<i class="mdi mdi-play-circle-outline"></i>';
        } else {
            th.innerHTML = '<i class="mdi mdi-file-document-outline"></i><em></em>';
            th.querySelector('em').textContent = extOf(p.file.name);
        }
        var rm = document.createElement('span');
        rm.className = 'wc-th-x';
        rm.setAttribute('data-rm', i);
        rm.setAttribute('role', 'button');
        rm.setAttribute('aria-label', 'Hapus ' + p.file.name);
        rm.innerHTML = '&times;';
        th.appendChild(rm);
        return th;
    }

    function renderCompose(scrollEnd) {
        if (!pending.length) { closeCompose(); return; }
        if (selected >= pending.length) { selected = pending.length - 1; }
        var cur = pending[selected];

        var total = pending.reduce(function (sum, p) { return sum + p.file.size; }, 0);
        composeTitle.textContent = pending.length === 1 ? cur.file.name : pending.length + ' file dipilih';
        composeSub.textContent = pending.length === 1 ? sizeText(cur.file.size) : 'Total ' + sizeText(total) + ' \u00b7 maks. ' + MAX_FILES + ' file';

        stage.innerHTML = '';
        stage.appendChild(stageEl(cur));

        var keep = strip.scrollLeft;
        strip.innerHTML = '';
        pending.forEach(function (p, i) { strip.appendChild(thumbEl(p, i)); });
        if (pending.length < MAX_FILES) {
            var add = document.createElement('button');
            add.type = 'button';
            add.className = 'wc-th wc-th--add';
            add.setAttribute('data-add', '1');
            add.setAttribute('aria-label', 'Tambah lampiran');
            add.innerHTML = '<i class="mdi mdi-plus"></i>';
            strip.appendChild(add);
        }
        strip.scrollLeft = scrollEnd ? strip.scrollWidth : keep;

        composeCount.hidden = pending.length < 2;
        composeCount.textContent = pending.length;
    }

    strip.addEventListener('click', function (e) {
        var rm = e.target.closest('[data-rm]');
        if (rm) {
            var idx = parseInt(rm.getAttribute('data-rm'), 10);
            var gone = pending.splice(idx, 1)[0];
            if (gone && gone.url) { URL.revokeObjectURL(gone.url); }
            if (idx < selected) { selected -= 1; }
            renderCompose();
            return;
        }
        if (e.target.closest('[data-add]')) { pickFiles(pickMode); return; }
        var th = e.target.closest('[data-i]');
        if (th) { selected = parseInt(th.getAttribute('data-i'), 10); renderCompose(); }
    });
    strip.addEventListener('keydown', function (e) {
        var th = e.target.closest && e.target.closest('[data-i]');
        if (th && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); selected = parseInt(th.getAttribute('data-i'), 10); renderCompose(); }
    });

    caption.addEventListener('input', function () { autosizeEl(caption, 110); });
    caption.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); sendCompose(); }
    });
    composeClose.addEventListener('click', closeCompose);
    composeSend.addEventListener('click', sendCompose);

    function sendCompose() {
        if (sending || !pending.length) { return; }

        var fd = new FormData();
        var text = caption.value.trim();
        if (text) { fd.append('body', text); }
        pending.forEach(function (p) { fd.append('files[]', p.file); });

        sending = true;
        composeSend.disabled = true;
        composeEl.classList.add('is-sending');
        postMessage(fd).then(function (x) {
            if (!x.r.ok) { flashError(errorFrom(x.r, x.json)); return false; }
            appendMessages([x.json.message]);
            input.value = '';
            autosize();
            return true;
        }).catch(function () {
            flashError('Pesan gagal terkirim. Periksa koneksi lalu coba lagi.');
            return false;
        }).then(function (ok) {
            sending = false;
            composeSend.disabled = false;
            composeEl.classList.remove('is-sending');
            if (ok) { closeCompose(); toBottom(); }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        if (!attachMenu.hidden) { setMenu(false); return; }
        if (composeOpen() && light.hidden) { closeCompose(); }
    });

    /* ---------- edit pesan (tidak ada hapus) ---------- */
    list.addEventListener('click', function (e) {
        var img = e.target.closest('[data-wc-img]');
        if (img) {
            e.preventDefault();
            light.querySelector('img').src = img.getAttribute('href');
            light.hidden = false;
            return;
        }

        var btn = e.target.closest('[data-wc-edit]');
        if (!btn) { return; }
        var row = btn.closest('[data-id]');
        var m = row._m;
        var textEl = row.querySelector('.wc-text');
        if (textEl.querySelector('textarea')) { return; }

        textEl.innerHTML = '<div class="wc-edit"><textarea maxlength="4000"></textarea>' +
            '<div class="wc-edit-actions"><button type="button" data-cancel>Batal</button><button type="button" class="ok" data-save>Simpan</button></div></div>';
        var ta = textEl.querySelector('textarea');
        ta.value = m.body || '';
        ta.focus();

        function cancel() { textEl.innerHTML = textHtml(row._m); }
        textEl.querySelector('[data-cancel]').addEventListener('click', cancel);
        ta.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { cancel(); } });
        textEl.querySelector('[data-save]').addEventListener('click', function () {
            var saveBtn = this;
            saveBtn.disabled = true;
            fetch(URLS.update + '/' + m.id, {
                method: 'PATCH', credentials: 'same-origin',
                headers: headers({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ body: ta.value })
            }).then(function (r) {
                return r.json().catch(function () { return null; }).then(function (json) { return { r: r, json: json }; });
            }).then(function (x) {
                if (!x.r.ok) { showError(errorFrom(x.r, x.json)); saveBtn.disabled = false; return; }
                var upd = x.json.message;
                row._m = upd;
                textEl.innerHTML = textHtml(upd);
                row.querySelector('.wc-edited').textContent = upd.edited ? 'diedit' : '';
            }).catch(function () { showError('Gagal menyimpan perubahan.'); saveBtn.disabled = false; });
        });
    });

    light.addEventListener('click', function (e) { if (e.target !== light.querySelector('img')) { light.hidden = true; } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { light.hidden = true; } });

    /* ---------- popup riwayat pengeditan ---------- */
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-wc-history]');
        if (!b) { return; }
        var modal = document.getElementById('wc-history-modal');
        var box = document.getElementById('wcHistoryList');
        if (modal) { modal.classList.add('is-open'); }
        box.innerHTML = '<div class="wc-empty">Memuat...</div>';
        fetchJson(URLS.history).then(function (res) {
            box.innerHTML = res.messages.length
                ? res.messages.map(function (m) { return sysHtml(m, true); }).join('')
                : '<div class="wc-empty">Belum ada pengeditan pada tugas ini.</div>';
        }).catch(function () { box.innerHTML = '<div class="wc-empty">Gagal memuat riwayat.</div>'; });
    });

    loadInitial();
})();
