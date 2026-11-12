/* Ruang chat tugas: pesan, edit pesan ("diedit"), lampiran foto/video/file dengan preview, catatan pengeditan tugas, riwayat. */
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
    var tray = document.getElementById('wcTray');
    var form = document.getElementById('wcForm');
    var input = document.getElementById('wcInput');
    var filesInput = document.getElementById('wcFiles');
    var sendBtn = document.getElementById('wcSend');
    var attachBtn = document.getElementById('wcAttach');
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
    var pending = [];     // file yang dipilih, belum terkirim
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
        if (m.kind === 'edit') {
            el.className = 'wc-sysrow';
            el.innerHTML = sysHtml(m, false);
        } else {
            el.className = 'wc-row' + (m.user.mine ? ' mine' : '');
            el.innerHTML = bubbleHtml(m);
        }
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
        if (m.kind === 'edit') { return; }
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

    /* ---------- lampiran (sebelum kirim) ---------- */
    function renderTray() {
        tray.hidden = pending.length === 0;
        tray.innerHTML = '';
        pending.forEach(function (f, i) {
            var chip = document.createElement('div');
            chip.className = 'wc-chip';
            if (f.type.indexOf('image/') === 0 && /^image\/(jpeg|png|gif|webp)$/.test(f.type)) {
                var img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                img.onload = function () { URL.revokeObjectURL(img.src); };
                chip.appendChild(img);
            } else {
                chip.innerHTML = '<i class="mdi ' + (f.type.indexOf('video/') === 0 ? 'mdi-play-circle-outline' : 'mdi-file-document-outline') + '"></i>';
            }
            var name = document.createElement('em');
            name.textContent = f.name;
            chip.appendChild(name);
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.setAttribute('aria-label', 'Hapus lampiran');
            rm.innerHTML = '&times;';
            rm.addEventListener('click', function () { pending.splice(i, 1); renderTray(); });
            chip.appendChild(rm);
            tray.appendChild(chip);
        });
    }

    function addFiles(fileList) {
        [].slice.call(fileList).forEach(function (f) {
            if (pending.length >= MAX_FILES) { showError('Maksimal ' + MAX_FILES + ' file per pesan.'); return; }
            if (f.size > MAX_SIZE) { showError(f.name + ': ukuran maksimal 25 MB.'); return; }
            var dup = pending.some(function (x) { return x.name === f.name && x.size === f.size && x.lastModified === f.lastModified; });
            if (!dup) { pending.push(f); }
        });
        renderTray();
    }

    attachBtn.addEventListener('click', function () { filesInput.click(); });
    filesInput.addEventListener('change', function () { addFiles(filesInput.files); filesInput.value = ''; });
    input.addEventListener('paste', function (e) {
        var files = e.clipboardData && e.clipboardData.files;
        if (files && files.length) { addFiles(files); }
    });

    /* ---------- kirim ---------- */
    function autosize() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 120) + 'px'; }
    input.addEventListener('input', autosize);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : sendBtn.click(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (sending) { return; }
        var text = input.value.trim();
        if (!text && !pending.length) { return; }

        var fd = new FormData();
        if (text) { fd.append('body', text); }
        pending.forEach(function (f) { fd.append('files[]', f); });

        sending = true;
        sendBtn.disabled = true;
        fetch(URLS.store, { method: 'POST', credentials: 'same-origin', headers: headers({}), body: fd }).then(function (r) {
            return r.json().catch(function () { return null; }).then(function (json) { return { r: r, json: json }; });
        }).then(function (x) {
            if (!x.r.ok) { showError(errorFrom(x.r, x.json)); return; }
            appendMessages([x.json.message]);
            input.value = '';
            autosize();
            pending = [];
            renderTray();
            toBottom();
        }).catch(function () { showError('Pesan gagal terkirim. Periksa koneksi lalu coba lagi.'); })
            .then(function () { sending = false; sendBtn.disabled = false; input.focus(); });
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
