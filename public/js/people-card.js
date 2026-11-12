/* Kartu peserta: render kartu, animasi putar, pencarian navbar, halaman Direktori. */
(function () {
    'use strict';

    var SVG = {
        code: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        user: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        school: '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
        bank: '<path d="M3 10l9-6 9 6"/><path d="M5 10v8M9 10v8M15 10v8M19 10v8M3 20h18"/>',
        calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>',
        phone: '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        pin: '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        cake: '<path d="M4 20h16v-7H4z"/><path d="M4 16c3 2 5-2 8 0s5-2 8 0"/><path d="M12 13V9M12 6.5c-.8-1-.8-1.6 0-2.5.8.9.8 1.5 0 2.5z"/>'
    };

    // urutan baris di kartu + ikon + label (sama dengan User::CARD_FIELDS di server)
    var FIELDS = [
        { k: 'division', label: 'Divisi', icon: 'code' },
        { k: 'mentor', label: 'Mentor', icon: 'user' },
        { k: 'major', label: 'Jurusan', icon: 'school' },
        { k: 'email', label: 'Email', icon: 'mail' },
        { k: 'institution', label: 'Asal Kampus', icon: 'bank' },
        { k: 'period', label: 'Periode Magang', icon: 'calendar' },
        { k: 'phone_number', label: 'Nomor Telepon', icon: 'phone' },
        { k: 'address', label: 'Alamat', icon: 'pin' },
        { k: 'birth_date', label: 'Tanggal Lahir', icon: 'cake' }
    ];
    var ICON_OF = {};
    FIELDS.forEach(function (f) { ICON_OF[f.k] = f.icon; });

    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fmtDate(iso) {
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso || '');
        return m ? m[3] + ' ' + MONTHS[parseInt(m[2], 10) - 1] + ' ' + m[1] : '';
    }

    function initialsOf(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
        return parts.length ? parts.map(function (w) { return w.charAt(0).toUpperCase(); }).join('') : '-';
    }

    function rowsHtml(rows) {
        return (rows || []).map(function (r) {
            return '<li><span class="pc-id-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
                'stroke-linecap="round" stroke-linejoin="round">' + (SVG[ICON_OF[r.k]] || SVG.user) + '</svg></span>' +
                '<div><small>' + esc(r.label) + '</small><b title="' + esc(r.value) + '">' + esc(r.value) + '</b></div></li>';
        }).join('');
    }

    function cardHtml(p, logo) {
        var photo = p.photo
            ? '<img src="' + esc(p.photo) + '" alt="">'
            : '<span class="pc-id-initials">' + esc(p.initials || initialsOf(p.name)) + '</span>';
        return '<div class="pc-id">' +
            '<div class="pc-id-top"><img class="pc-id-logo" src="' + esc(logo) + '" alt="PLN Suku Cadang"><span class="pc-id-badge">Intern</span></div>' +
            '<div class="pc-id-photo">' + photo + '</div>' +
            '<div class="pc-id-name"><i>&#10022;</i><span>' + esc(p.name) + '</span><i>&#10022;</i></div>' +
            '<ul class="pc-id-info">' + rowsHtml(p.rows) + '</ul>' +
            '<div class="pc-id-foot">Learn &middot; Contribute &middot; Grow</div></div>';
    }

    /* ---------- Overlay: kartu berputar lalu berhenti (satu kartu atau beberapa sekaligus) ---------- */
    var overlay = null;
    var settleTimer = null;

    function closeCard() {
        if (overlay) {
            clearTimeout(settleTimer);
            overlay.classList.remove('is-open', 'is-settled');
            overlay.firstChild.innerHTML = '';
        }
    }

    function openCard(list, logo) {
        var people = Array.isArray(list) ? list : [list];
        if (!people.length) { return; }
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'pc-overlay';
            overlay.innerHTML = '<div class="pc-stage"></div><button type="button" class="pc-overlay-x" aria-label="Tutup">&times;</button>';
            document.body.appendChild(overlay);
            overlay.addEventListener('click', function (e) {
                if (!e.target.closest('.pc-flip')) { closeCard(); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { closeCard(); }
            });
        }
        var back = '<div class="pc-back"><img src="' + esc(logo) + '" alt="PLN Suku Cadang"></div>';
        overlay.firstChild.innerHTML = people.map(function (p, i) {
            return '<div class="pc-fit"><div class="pc-flip" style="--pc-delay:' + (i * 0.18) + 's">' + back + cardHtml(p, logo) + '</div></div>';
        }).join('');

        clearTimeout(settleTimer);
        overlay.classList.remove('is-open', 'is-settled');
        void overlay.offsetWidth; // mulai ulang animasi
        overlay.classList.add('is-open');
        // gulir baru aktif setelah animasi selesai (mencegah scrollbar berkedip saat kartu berputar)
        settleTimer = setTimeout(function () { overlay.classList.add('is-settled'); }, 2600 + people.length * 180 + 150);
    }

    function getJson(url, signal) {
        return fetch(url, {
            credentials: 'same-origin',
            signal: signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, self = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(self, args); }, ms);
        };
    }

    var cfg = { logo: '', searchUrl: '', directoryUrl: '' };

    /* ---------- Kolom cari di navbar ---------- */
    function initNavbarSearch(form) {
        var input = form.querySelector('.pc-search-input');
        var box = form.querySelector('.pc-search-results');
        var hits = [];
        var active = -1;
        var ctrl = null;

        function hide() { box.hidden = true; active = -1; }

        function highlight(i) {
            var items = box.querySelectorAll('.pc-hit');
            [].forEach.call(items, function (el, idx) { el.classList.toggle('is-active', idx === i); });
            active = i;
        }

        function render(list, total, q) {
            hits = list;
            if (!list.length) {
                box.innerHTML = '<div class="pc-hit-empty">Tidak ada peserta bernama &ldquo;' + esc(q) + '&rdquo;.</div>';
            } else {
                box.innerHTML = list.map(function (p, i) {
                    var av = p.photo ? '<img src="' + esc(p.photo) + '" alt="">' : esc(p.initials);
                    var division = '';
                    p.rows.forEach(function (r) { if (r.k === 'division') { division = r.value; } });
                    return '<button type="button" class="pc-hit" data-i="' + i + '"><span class="pc-hit-av">' + av + '</span>' +
                        '<span class="pc-hit-txt"><b>' + esc(p.name) + '</b><small>' + esc(division || 'Peserta magang') + '</small></span></button>';
                }).join('') +
                    '<a class="pc-hit-all" href="' + esc(cfg.directoryUrl + '?q=' + encodeURIComponent(q)) + '">Lihat semua hasil' +
                    (total > list.length ? ' (' + total + ')' : '') + '</a>';
            }
            box.hidden = false;
            active = -1;
        }

        var run = debounce(function () {
            var q = input.value.trim();
            if (!q) { hide(); return; }
            if (ctrl) { ctrl.abort(); }
            ctrl = window.AbortController ? new AbortController() : null;
            getJson(cfg.searchUrl + '?limit=6&q=' + encodeURIComponent(q), ctrl && ctrl.signal)
                .then(function (res) { if (input.value.trim() === q) { render(res.data, res.total, q); } })
                .catch(function () { /* diabaikan (dibatalkan / gangguan jaringan) */ });
        }, 250);

        input.addEventListener('input', run);
        input.addEventListener('focus', function () { if (input.value.trim() && box.innerHTML) { box.hidden = false; } });
        input.addEventListener('keydown', function (e) {
            var items = box.querySelectorAll('.pc-hit');
            if (e.key === 'ArrowDown' && items.length) { e.preventDefault(); highlight((active + 1) % items.length); }
            else if (e.key === 'ArrowUp' && items.length) { e.preventDefault(); highlight((active - 1 + items.length) % items.length); }
            else if (e.key === 'Enter' && active > -1 && hits[active]) { e.preventDefault(); hide(); openCard(hits[active], cfg.logo); }
            else if (e.key === 'Escape') { hide(); }
        });
        box.addEventListener('click', function (e) {
            var b = e.target.closest('.pc-hit');
            if (b && hits[+b.getAttribute('data-i')]) { hide(); openCard(hits[+b.getAttribute('data-i')], cfg.logo); }
        });
        document.addEventListener('click', function (e) { if (!form.contains(e.target)) { hide(); } });
    }

    /* ---------- Halaman Direktori ---------- */
    function initDirectory(root) {
        var qInput = root.querySelector('#pcDirQ');
        var divSel = root.querySelector('#pcDirDiv');
        var grid = root.querySelector('#pcDirGrid');
        var count = root.querySelector('#pcDirCount');
        var more = root.querySelector('#pcDirMore');
        var people = {};
        var nextPage = null;
        var ctrl = null;
        var seq = 0;

        function url(page) {
            return cfg.searchUrl + '?page=' + page + '&limit=24&q=' + encodeURIComponent(qInput.value.trim()) +
                (divSel.value ? '&division=' + encodeURIComponent(divSel.value) : '');
        }

        function load(reset) {
            var mine = ++seq;
            if (reset) { people = {}; nextPage = 1; grid.innerHTML = '<div class="pc-dir-empty" style="grid-column:1/-1">Memuat…</div>'; more.hidden = true; }
            if (ctrl) { ctrl.abort(); }
            ctrl = window.AbortController ? new AbortController() : null;
            getJson(url(nextPage), ctrl && ctrl.signal).then(function (res) {
                if (mine !== seq) { return; }
                if (reset) { grid.innerHTML = ''; }
                res.data.forEach(function (p) {
                    people[p.id] = p;
                    var tile = document.createElement('button');
                    tile.type = 'button';
                    tile.className = 'pc-tile';
                    tile.setAttribute('data-id', p.id);
                    tile.setAttribute('aria-label', 'Buka kartu ' + p.name);
                    tile.innerHTML = cardHtml(p, cfg.logo);
                    grid.appendChild(tile);
                });
                nextPage = res.next_page;
                more.hidden = !nextPage;
                count.textContent = res.total + ' peserta';
                if (!grid.children.length) {
                    grid.innerHTML = '<div class="pc-dir-empty" style="grid-column:1/-1">Tidak ada peserta yang cocok.</div>';
                }
            }).catch(function (err) {
                if (err && err.name === 'AbortError') { return; }
                if (mine === seq) { grid.innerHTML = '<div class="pc-dir-empty" style="grid-column:1/-1">Gagal memuat data. Coba muat ulang halaman.</div>'; }
            });
        }

        grid.addEventListener('click', function (e) {
            var t = e.target.closest('.pc-tile');
            if (t && people[t.getAttribute('data-id')]) { openCard(people[t.getAttribute('data-id')], cfg.logo); }
        });
        qInput.addEventListener('input', debounce(function () { load(true); }, 250));
        qInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
        divSel.addEventListener('change', function () { load(true); });
        more.addEventListener('click', function () { if (nextPage) { load(false); } });

        load(true);
    }

    /* ---------- Pemilih rekan di Log Pekerjaan: kartu + animasi naik sebelum kirim ---------- */
    function initPicker(grid) {
        var logo = grid.getAttribute('data-logo') || cfg.logo;
        var picks = [].slice.call(grid.querySelectorAll('.pc-pick'));

        picks.forEach(function (label) {
            try {
                var person = JSON.parse(label.getAttribute('data-person'));
                label.querySelector('.pc-pick-card').innerHTML = cardHtml(person, logo);
                // pencarian hanya mencakup yang tampil di kartu (isian tersembunyi tidak bisa ditebak lewat pencarian)
                label.setAttribute('data-search', [person.name].concat(person.rows.map(function (r) { return r.value; })).join(' ').toLowerCase());
            } catch (err) { /* biarkan tampilan cadangan (nama saja) */ }
        });

        var form = grid.closest('form');
        if (!form) { return; }
        var sending = false;

        form.addEventListener('submit', function (e) {
            if (sending) { e.preventDefault(); return; }

            var chosen = picks.filter(function (l) { return l.querySelector('input').checked; });
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!chosen.length || reduce || !Element.prototype.animate) { return; } // kirim biasa

            // isian wajib belum lengkap: biarkan browser menampilkan pesannya
            if (form.checkValidity && !form.checkValidity()) { return; }

            e.preventDefault();
            sending = true;
            [].forEach.call(form.querySelectorAll('button[type=submit]'), function (b) { b.disabled = true; });

            var finished = 0;
            function done() {
                finished++;
                if (finished === chosen.length) { form.submit(); } // notifikasi "Berhasil" muncul di halaman berikutnya
            }

            chosen.forEach(function (label, i) {
                var card = label.querySelector('.pc-id');
                var r = card.getBoundingClientRect();
                var fly = document.createElement('div');
                fly.className = 'pc-fly';
                fly.style.cssText = 'left:' + r.left + 'px;top:' + r.top + 'px;width:' + r.width + 'px;height:' + r.height + 'px;';
                fly.appendChild(card.cloneNode(true));
                document.body.appendChild(fly);
                label.classList.add('is-flown');

                var rise = -(r.bottom + 80);
                var anim = fly.animate([
                    { transform: 'translateY(0) scale(1)', opacity: 1, offset: 0 },
                    { transform: 'translateY(12px) scale(1.04)', opacity: 1, offset: 0.18 },
                    { transform: 'translateY(' + rise + 'px) scale(.82)', opacity: 0.1, offset: 1 }
                ], { duration: 950, delay: i * 110, easing: 'cubic-bezier(.5,0,.75,.4)', fill: 'both' });
                anim.onfinish = done;
                anim.oncancel = done;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        [].forEach.call(document.querySelectorAll('[data-pc-pick]'), initPicker);
        var navForm = document.querySelector('form.pc-search[data-search-url]');
        if (navForm) {
            cfg.logo = navForm.getAttribute('data-logo') || '';
            cfg.searchUrl = navForm.getAttribute('data-search-url');
            cfg.directoryUrl = navForm.getAttribute('data-directory-url');
            initNavbarSearch(navForm);
        }
        var dir = document.getElementById('pcDirectory');
        if (dir && cfg.searchUrl) { initDirectory(dir); }
    });

    window.PeopleCard = { FIELDS: FIELDS, esc: esc, fmtDate: fmtDate, initialsOf: initialsOf, rowsHtml: rowsHtml, cardHtml: cardHtml, open: openCard };
})();
