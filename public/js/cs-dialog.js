/* CSDialog: pop up kustom pengganti SweetAlert, confirm() dan alert() bawaan browser. Tanpa dependensi.
 *
 *  CSDialog.alert({ level, title, message, timer, confirmText })  -> Promise
 *  CSDialog.confirm({ level, title, message, confirmText, cancelText, danger })  -> Promise<boolean>
 *
 *  Otomatis untuk HTML:
 *    <form data-confirm="Hapus data ini?"> ... </form>
 *    <a|button data-confirm="...">
 *    Opsi: data-confirm-title, data-confirm-ok, data-confirm-level (question|warning|info), data-confirm-tone="danger"
 */
(function (w, d) {
    'use strict';
    if (w.CSDialog) { return; }

    var CIRCLE = '<circle class="csd-ring" cx="26" cy="26" r="24"/>';
    var ICONS = {
        success: CIRCLE + '<path class="csd-mark" d="M15 27l8 8 15-17"/>',
        error: CIRCLE + '<path class="csd-mark" d="M18 18l16 16M34 18L18 34"/>',
        warning: CIRCLE + '<path class="csd-mark" d="M26 14v15"/><path class="csd-mark" d="M26 37v.01"/>',
        info: CIRCLE + '<path class="csd-mark" d="M26 24v14"/><path class="csd-mark" d="M26 15v.01"/>',
        question: CIRCLE + '<path class="csd-mark" d="M20 21a6 6 0 1 1 9 5c-2 1.3-3 2.3-3 5"/><path class="csd-mark" d="M26 38v.01"/>'
    };
    var TITLES = { success: 'Berhasil', error: 'Gagal', warning: 'Perhatian', info: 'Informasi', question: 'Konfirmasi' };

    var queue = [];
    var current = null;
    var seq = 0;

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function normalizeLevel(level, fallback) {
        level = String(level || '').toLowerCase();
        if (level === 'danger') { level = 'error'; }
        return ICONS[level] ? level : fallback;
    }

    function show(opts) {
        return new Promise(function (resolve) {
            queue.push({ o: opts, resolve: resolve });
            next();
        });
    }

    function next() {
        if (current || !queue.length) { return; }
        if (!d.body) {
            d.addEventListener('DOMContentLoaded', next);
            return;
        }
        render(queue.shift());
    }

    function render(job) {
        var o = job.o;
        var isConfirm = o.type === 'confirm';
        var level = normalizeLevel(o.level, isConfirm ? 'question' : 'info');
        var tone = o.danger ? 'danger' : level;
        var title = o.title != null && o.title !== '' ? o.title : TITLES[level];
        var id = 'csd-' + (++seq);
        var previous = d.activeElement;

        var backdrop = d.createElement('div');
        backdrop.className = 'csd-backdrop';
        backdrop.innerHTML =
            '<div class="csd-box csd-' + tone + '" role="dialog" aria-modal="true" aria-labelledby="' + id + '-t" aria-describedby="' + id + '-m" tabindex="-1">' +
            '<div class="csd-icon"><svg viewBox="0 0 52 52" aria-hidden="true">' + ICONS[o.danger ? 'warning' : level] + '</svg></div>' +
            '<h3 class="csd-title" id="' + id + '-t">' + esc(title) + '</h3>' +
            (o.message ? '<p class="csd-msg" id="' + id + '-m">' + esc(o.message) + '</p>' : '') +
            '<div class="csd-actions">' +
            (isConfirm ? '<button type="button" class="csd-btn csd-btn--ghost" data-csd="cancel">' + esc(o.cancelText || 'Batal') + '</button>' : '') +
            '<button type="button" class="csd-btn csd-btn--primary" data-csd="ok">' + esc(o.confirmText || (isConfirm ? 'Ya' : 'Oke')) + '</button>' +
            '</div>' +
            (o.timer > 0 ? '<div class="csd-timer"><i style="animation-duration:' + o.timer + 'ms"></i></div>' : '') +
            '</div>';

        var box = backdrop.firstChild;
        var okBtn = backdrop.querySelector('[data-csd="ok"]');
        var timeout = null;
        var remaining = o.timer > 0 ? o.timer : 0;
        var startedAt = 0;
        var closed = false;

        function close(value) {
            if (closed) { return; }
            closed = true;
            clearTimeout(timeout);
            d.removeEventListener('keydown', onKey, true);
            backdrop.classList.add('csd-out');
            setTimeout(function () {
                if (backdrop.parentNode) { backdrop.parentNode.removeChild(backdrop); }
                if (previous && previous.focus) { try { previous.focus(); } catch (e) {} }
                current = null;
                job.resolve(value);
                next();
            }, 160);
        }

        function startTimer() {
            if (!remaining) { return; }
            startedAt = Date.now();
            timeout = setTimeout(function () { close(true); }, remaining);
        }

        function onKey(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                close(isConfirm ? false : true);
            } else if (e.key === 'Tab') {
                var f = backdrop.querySelectorAll('button');
                if (!f.length) { return; }
                var first = f[0], last = f[f.length - 1];
                if (e.shiftKey && d.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && d.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        }

        backdrop.addEventListener('click', function (e) {
            var action = e.target.getAttribute && e.target.getAttribute('data-csd');
            if (action === 'ok') { close(true); }
            else if (action === 'cancel' || e.target === backdrop) { close(isConfirm ? false : true); }
        });

        // Timer berhenti saat kursor di atas dialog.
        if (remaining) {
            box.addEventListener('mouseenter', function () {
                clearTimeout(timeout);
                remaining -= Date.now() - startedAt;
                var bar = box.querySelector('.csd-timer i');
                if (bar) { bar.style.animationPlayState = 'paused'; }
            });
            box.addEventListener('mouseleave', function () {
                var bar = box.querySelector('.csd-timer i');
                if (bar) { bar.style.animationPlayState = 'running'; }
                startTimer();
            });
        }

        current = job;
        d.body.appendChild(backdrop);
        d.addEventListener('keydown', onKey, true);
        okBtn.focus();
        startTimer();
    }

    function toOptions(arg, type, defaults) {
        var o = typeof arg === 'string' ? { message: arg } : (arg || {});
        var out = { type: type };
        for (var k in defaults) { if (defaults.hasOwnProperty(k)) { out[k] = defaults[k]; } }
        for (var j in o) { if (o.hasOwnProperty(j)) { out[j] = o[j]; } }
        return out;
    }

    w.CSDialog = {
        alert: function (arg) {
            var o = toOptions(arg, 'alert', {});
            if (o.timer == null) { o.timer = 0; }
            return show(o);
        },
        confirm: function (arg) { return show(toOptions(arg, 'confirm', {})); },
        success: function (message, title) { return w.CSDialog.alert({ level: 'success', title: title, message: message, timer: 2500 }); },
        error: function (message, title) { return w.CSDialog.alert({ level: 'error', title: title, message: message }); },
        warning: function (message, title) { return w.CSDialog.alert({ level: 'warning', title: title, message: message }); },
        info: function (message, title) { return w.CSDialog.alert({ level: 'info', title: title, message: message }); },

        /** Dipakai halaman flash: success/info menutup otomatis, error/warning menunggu tombol. */
        flash: function (level, title, message, sticky) {
            level = normalizeLevel(level, 'info');
            var auto = !sticky && (level === 'success' || level === 'info');
            return w.CSDialog.alert({ level: level, title: title, message: message, timer: auto ? 2500 : 0 });
        }
    };

    // alert() bawaan browser -> dialog kustom.
    w.alert = function (message) {
        w.CSDialog.alert({ level: 'info', title: 'Perhatian', message: String(message == null ? '' : message) });
    };

    // ----- Otomatis: data-confirm pada form / tautan / tombol -----
    function ask(el) {
        var msg = el.getAttribute('data-confirm') || '';
        var tone = el.getAttribute('data-confirm-tone');
        var danger = tone ? tone === 'danger' : /(hapus|nonaktifkan|tarik|reset|keluarkan)/i.test(msg);
        return w.CSDialog.confirm({
            level: el.getAttribute('data-confirm-level') || 'question',
            title: el.getAttribute('data-confirm-title') || (danger ? 'Yakin ingin melanjutkan?' : 'Konfirmasi'),
            message: msg,
            confirmText: el.getAttribute('data-confirm-ok') || (danger ? 'Ya, lanjutkan' : 'Ya'),
            cancelText: 'Batal',
            danger: danger
        });
    }

    d.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm') || form.__csdOk) { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        var submitter = e.submitter || null;
        ask(form).then(function (ok) {
            if (!ok) { return; }
            form.__csdOk = true;
            try {
                if (form.requestSubmit) { submitter ? form.requestSubmit(submitter) : form.requestSubmit(); }
                else { form.submit(); }
            } catch (err) {
                form.submit();
            }
            setTimeout(function () { form.__csdOk = false; }, 0);
        });
    }, true);

    d.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('a[data-confirm], button[data-confirm]') : null;
        if (!el || el.__csdOk) { return; }
        // Tombol submit di dalam form ber-data-confirm sudah ditangani oleh handler submit.
        if (el.tagName === 'BUTTON' && el.form && el.form.hasAttribute('data-confirm')) { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        ask(el).then(function (ok) {
            if (!ok) { return; }
            el.__csdOk = true;
            el.click();
            el.__csdOk = false;
        });
    }, true);
})(window, document);
