/* Log Pekerjaan: kartu putar peserta, popup, pemilih peserta. Tanpa dependensi. */
(function () {
    'use strict';

    var overlay = null;

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    var ICONS = {
        code: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        school: '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
        mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>',
        bank: '<path d="M3 10l9-6 9 6"/><path d="M5 10v8M9 10v8M15 10v8M19 10v8M3 20h18"/>',
        calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>'
    };

    function infoRow(icon, label, value) {
        return '<li><span class="wl-id-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round">' + ICONS[icon] + '</svg></span>' +
            '<div><small>' + label + '</small><b>' + esc(value) + '</b></div></li>';
    }

    // Kartu ID: peserta hanya melihat nama + divisi; mentor/admin melihat data lengkap.
    function frontHtml(p, logo) {
        var photo = p.photo ? '<img src="' + esc(p.photo) + '" alt="">' : '<span class="wl-id-initials">' + esc(p.initials) + '</span>';
        var rows = infoRow('code', 'Divisi', p.division);
        if (p.detail) {
            rows += infoRow('school', 'Jurusan', p.major) +
                infoRow('mail', 'Email', p.email) +
                infoRow('bank', 'Asal Kampus', p.institution) +
                infoRow('calendar', 'Periode Magang', p.period);
        }
        return '<div class="wl-face wl-face--front wl-id">' +
            '<div class="wl-id-top"><img class="wl-id-logo" src="' + esc(logo) + '" alt="PLN Suku Cadang"><span class="wl-id-badge">Intern</span></div>' +
            '<div class="wl-id-photo">' + photo + '</div>' +
            '<div class="wl-id-name"><i>&#10022;</i><span>' + esc(p.name) + '</span><i>&#10022;</i></div>' +
            '<ul class="wl-id-info">' + rows + '</ul>' +
            '<div class="wl-id-foot">Learn &middot; Contribute &middot; Grow</div></div>';
    }

    var settleTimer = null;

    function openCards(people, logo) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'wl-overlay';
            overlay.innerHTML = '<div class="wl-flip-stage"></div>';
            document.body.appendChild(overlay);
        }
        var stage = overlay.firstChild;
        stage.innerHTML = '';
        people.forEach(function (p, i) {
            var card = document.createElement('div');
            card.className = 'wl-flip';
            card.style.setProperty('--wl-delay', (i * 0.18) + 's');
            card.innerHTML =
                '<div class="wl-face wl-face--back"><img src="' + esc(logo) + '" alt="PLN Suku Cadang"></div>' +
                frontHtml(p, logo);
            stage.appendChild(card);
        });
        overlay.classList.remove('is-settled');
        overlay.classList.add('is-open');

        // Scrollbar baru boleh muncul setelah animasi putar selesai (mencegah kedipan scrollbar).
        clearTimeout(settleTimer);
        settleTimer = setTimeout(function () { overlay.classList.add('is-settled'); }, 2600 + people.length * 180 + 150);
    }

    function closeCards() {
        if (overlay) {
            clearTimeout(settleTimer);
            overlay.classList.remove('is-open', 'is-settled');
            overlay.firstChild.innerHTML = '';
        }
    }

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
            try { openCards(JSON.parse(btn.getAttribute('data-people') || '[]'), btn.getAttribute('data-logo')); } catch (err) {}
            return;
        }

        // klik di luar kartu -> kembali ke halaman
        if (overlay && overlay.classList.contains('is-open') && !t.closest('.wl-flip')) {
            closeCards();
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
        if (e.key === 'Escape') { closeCards(); closeModals(); }
    });

    // pemilih peserta berbentuk kartu
    function refreshCount(scope) {
        var n = scope.querySelectorAll('.wl-pcard input:checked').length;
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
        if (input.matches && input.matches('.wl-pcard input')) {
            input.closest('.wl-pcard').classList.toggle('is-selected', input.checked);
            refreshCount(input.closest('[data-wl-picker]') || document);
        }
    });

    document.addEventListener('input', function (e) {
        var box = e.target;
        if (box.matches && box.matches('[data-wl-psearch]')) {
            var q = box.value.trim().toLowerCase();
            var scope = box.closest('[data-wl-picker]') || document;
            [].forEach.call(scope.querySelectorAll('.wl-pcard'), function (c) {
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

    document.addEventListener('DOMContentLoaded', function () {
        tickCountdown();
        [].forEach.call(document.querySelectorAll('[data-wl-picker]'), refreshCount);
    });
})();
