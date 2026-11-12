/* Log Pekerjaan: popup, tab, menu, lampiran banyak file/tautan, pemilih peserta. Kartu putar peserta memakai PeopleCard (people-card.js). */
(function () {
    'use strict';

    function closeModals() {
        [].forEach.call(document.querySelectorAll('.wl-modal.is-open'), function (m) { m.classList.remove('is-open'); });
    }

    function closeMenus(except) {
        [].forEach.call(document.querySelectorAll('.wl-menu.is-open'), function (m) { if (m !== except) { m.classList.remove('is-open'); } });
    }

    document.addEventListener('click', function (e) {
        var t = e.target;

        // menu dropdown (lampirkan / opsi lampiran)
        var menuBtn = t.closest ? t.closest('[data-wl-menu]') : null;
        if (menuBtn) {
            var menu = menuBtn.closest('.wl-menu-wrap').querySelector('.wl-menu');
            closeMenus(menu);
            menu.classList.toggle('is-open');
            return;
        }
        var uploader = t.closest ? t.closest('[data-wl-upload]') : null;
        if (uploader) {
            closeMenus();
            var input = document.querySelector(uploader.getAttribute('data-wl-upload'));
            if (input) { input.click(); }
            return;
        }
        if (!t.closest('.wl-menu')) { closeMenus(); } else if (t.closest('[data-wl-open]')) { closeMenus(); }

        // ikon pengguna -> kartu putar
        var btn = t.closest ? t.closest('.wl-mate') : null;
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            try {
                if (window.PeopleCard) { window.PeopleCard.open(JSON.parse(btn.getAttribute('data-people') || '[]'), btn.getAttribute('data-logo')); }
            } catch (err) {}
            return;
        }

        // popup (buka / tutup)
        var opener = t.closest('[data-wl-open]');
        if (opener) {
            var target = document.querySelector(opener.getAttribute('data-wl-open'));
            if (target) { target.classList.add('is-open'); }
            return;
        }
        if (t.closest('[data-wl-close]') || t.classList.contains('wl-modal')) { closeModals(); return; }

        // tab
        var tab = t.closest('[data-wl-tab]');
        if (tab) {
            var scope = tab.closest('.wl-modal') || document;
            [].forEach.call(scope.querySelectorAll('.wl-tab'), function (x) { x.classList.remove('is-active'); });
            [].forEach.call(scope.querySelectorAll('.wl-tabpane'), function (x) { x.classList.remove('is-active'); });
            tab.classList.add('is-active');
            var pane = scope.querySelector(tab.getAttribute('data-wl-tab'));
            if (pane) { pane.classList.add('is-active'); }
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeModals(); }
    });

    // pemilih peserta berbentuk kartu
    function refreshCount(scope) {
        var n = scope.querySelectorAll('.wl-pcard input:checked, .pc-pick input:checked').length;
        var out = scope.querySelector('[data-wl-count]');
        if (out) { out.textContent = n + ' dipilih'; }
    }

    document.addEventListener('change', function (e) {
        var input = e.target;

        // pilih file dari perangkat -> langsung unggah (boleh banyak file)
        if (input.id === 'wl-upload-input' && input.files && input.files.length) {
            input.form.submit();
            return;
        }
        if (input.matches && input.matches('.wl-pcard input, .pc-pick input')) {
            input.closest('.wl-pcard, .pc-pick').classList.toggle('is-selected', input.checked);
            refreshCount(input.closest('[data-wl-picker]') || document);
        }
    });

    document.addEventListener('input', function (e) {
        var box = e.target;
        if (box.matches && box.matches('[data-wl-psearch]')) {
            var q = box.value.trim().toLowerCase();
            var scope = box.closest('[data-wl-picker]') || document;
            [].forEach.call(scope.querySelectorAll('.wl-pcard, .pc-pick'), function (c) {
                c.classList.toggle('is-hidden', q !== '' && (c.getAttribute('data-search') || '').indexOf(q) === -1);
            });
        }
    });

    // Hitung mundur tenggat: > 1 hari = hari, < 1 hari = jam, < 1 jam = menit.
    function tickCountdown() {
        [].forEach.call(document.querySelectorAll('[data-wl-end]'), function (el) {
            var ms = Date.parse(el.getAttribute('data-wl-end')) - Date.now();
            var label = el.querySelector('span');
            if (isNaN(ms) || !label) { return; }
            if (ms <= 0) {
                label.textContent = 'Lewat tenggat';
                el.classList.remove('wl-badge--info');
                el.classList.add('wl-badge--late');
                return;
            }
            var m = Math.floor(ms / 60000);
            var urgent = m < 1440;
            label.textContent = m >= 1440 ? Math.floor(m / 1440) + ' hari lagi' : (m >= 60 ? Math.floor(m / 60) + ' jam lagi' : Math.max(1, m) + ' menit lagi');
            el.classList.toggle('wl-badge--late', urgent);
            el.classList.toggle('wl-badge--info', !urgent);
        });
    }
    setInterval(tickCountdown, 30000);

    // Lampiran pada form tugas: banyak file dan banyak tautan lewat menu "Lampirkan", dikirim bersama form.
    // Atribut pada root [data-wl-attach]:
    //   data-wl-link-name / data-wl-label-name : nama field tautan & nama tautan (dikirim sebagai name[])
    //   data-wl-remove-name                    : nama field id lampiran lama yang dihapus (mode edit)
    //   data-wl-existing  (JSON)               : lampiran lama [{id,type,name,url}]
    //   data-wl-links     (JSON)               : tautan awal [{url,label}] (mis. setelah validasi gagal)
    function initAttach(root) {
        var input = root.querySelector('[data-wl-attach-files]');
        var out = root.querySelector('[data-wl-attach-out]');
        var list = root.querySelector('[data-wl-attach-list]');
        var modal = document.querySelector(root.getAttribute('data-wl-attach-modal') || '#none');
        var mUrl = modal && modal.querySelector('[data-wl-modal-url]');
        var mLabel = modal && modal.querySelector('[data-wl-modal-label]');
        var mAdd = modal && modal.querySelector('[data-wl-modal-add]');
        var linkName = root.getAttribute('data-wl-link-name') || 'link_urls';
        var labelName = root.getAttribute('data-wl-label-name') || 'link_labels';
        var removeName = root.getAttribute('data-wl-remove-name');
        var files = [];
        var links = [];
        var existing = [];
        var canMerge = typeof DataTransfer !== 'undefined';
        if (!input || !out || !list) { return; }

        function parse(attr) { try { return JSON.parse(root.getAttribute(attr) || '[]') || []; } catch (err) { return []; } }
        links = parse('data-wl-links').filter(function (l) { return l && l.url; });
        existing = parse('data-wl-existing').map(function (x) { x.removed = false; return x; });

        function esc(v) {
            return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function size(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }

        function sync() {
            if (!canMerge) { return; }
            try {
                var dt = new DataTransfer();
                files.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
            } catch (err) { canMerge = false; }
        }

        function chip(icon, text, extra, key, href) {
            var label = href
                ? '<a class="t" href="' + esc(href) + '" target="_blank" rel="noopener" title="' + esc(text) + '">' + esc(text) + '</a>'
                : '<span class="t" title="' + esc(text) + '">' + esc(text) + '</span>';
            return '<span class="wl-attach-chip"><i class="mdi ' + icon + '"></i>' + label +
                (extra ? '<small>' + esc(extra) + '</small>' : '') +
                '<button type="button" data-wl-rm="' + key + '" aria-label="Hapus lampiran">&times;</button></span>';
        }

        function render() {
            var html = '';
            existing.forEach(function (x) {
                if (!x.removed) { html += chip(x.type === 'link' ? 'mdi-link-variant' : 'mdi-paperclip', x.name, 'tersimpan', 'x:' + x.id, x.url); }
            });
            files.forEach(function (f, i) { html += chip('mdi-paperclip', f.name, size(f.size), 'f:' + i); });
            links.forEach(function (l, i) { html += chip('mdi-link-variant', l.label || l.url, '', 'l:' + i); });
            list.innerHTML = html;

            // input tersembunyi yang ikut terkirim bersama form
            var hidden = '';
            links.forEach(function (l) {
                hidden += '<input type="hidden" name="' + esc(linkName) + '[]" value="' + esc(l.url) + '">' +
                    '<input type="hidden" name="' + esc(labelName) + '[]" value="' + esc(l.label || '') + '">';
            });
            if (removeName) {
                existing.forEach(function (x) {
                    if (x.removed) { hidden += '<input type="hidden" name="' + esc(removeName) + '[]" value="' + esc(x.id) + '">'; }
                });
            }
            out.innerHTML = hidden;
        }

        input.addEventListener('change', function () {
            var picked = [].slice.call(input.files);
            if (canMerge) {
                picked.forEach(function (f) {
                    var dup = files.some(function (x) { return x.name === f.name && x.size === f.size && x.lastModified === f.lastModified; });
                    if (!dup) { files.push(f); }
                });
                sync();
            } else {
                files = picked;
            }
            render();
        });

        list.addEventListener('click', function (e) {
            var b = e.target.closest('[data-wl-rm]');
            if (!b) { return; }
            var key = b.getAttribute('data-wl-rm');
            var n = key.slice(2);
            if (key.charAt(0) === 'l') { links.splice(parseInt(n, 10), 1); }
            else if (key.charAt(0) === 'x') { existing.forEach(function (x) { if (String(x.id) === n) { x.removed = true; } }); }
            else if (canMerge) { files.splice(parseInt(n, 10), 1); sync(); }
            else { files = []; input.value = ''; }
            render();
        });

        if (modal && mUrl && mAdd) {
            root.addEventListener('click', function (e) {
                var o = e.target.closest('[data-wl-open]');
                if (o && o.getAttribute('data-wl-open') === '#' + modal.id) {
                    mUrl.value = '';
                    if (mLabel) { mLabel.value = ''; }
                    setTimeout(function () { mUrl.focus(); }, 50);
                }
            });
            mAdd.addEventListener('click', function () {
                var u = mUrl.value.trim();
                if (!u || !mUrl.checkValidity()) { if (mUrl.reportValidity) { mUrl.reportValidity(); } return; }
                if (!links.some(function (l) { return l.url === u; })) {
                    links.push({ url: u, label: mLabel ? mLabel.value.trim() : '' });
                }
                render();
                modal.classList.remove('is-open');
            });
            [mUrl, mLabel].filter(Boolean).forEach(function (el) {
                el.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); mAdd.click(); } });
            });
        }

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        tickCountdown();
        [].forEach.call(document.querySelectorAll('[data-wl-attach]'), initAttach);
        [].forEach.call(document.querySelectorAll('[data-wl-picker]'), refreshCount);
    });
})();
