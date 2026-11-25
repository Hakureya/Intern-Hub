/*
 * Perayaan jam pulang bertema PLN. 6 animasi, satu dipilih acak per hari (tidak sama dengan kemarin).
 * Pakai: ClockoutCelebration.play({ time: '17:02', message: '...', userId: 1 });
 * Uji coba: tambahkan ?preview_celebration=1..6 pada URL halaman absensi.
 */
(function (w, d) {
    'use strict';

    var COLORS = ['#FFC400', '#1E73BE', '#29B6F6', '#E4262C'];
    var BOLT = '160,92 124,160 147,160 137,210 182,138 156,138';
    // Logo PLN (hasil tracing logo asli, kanvas 250x250): petir + 3 gelombang sinus.
    var PLN_BOLT = '140,34 109,34 71,156 137,129 120,216 168,95 105,120';
    var PLN_WAVES = { lam: 53.36, x0: 51, x1: 198, bands: [[87.54, 3.42, 8.74, 15.83], [126.68, -3.5, 5.62, 16.11], [165.54, 3.47, 8.74, 16.29]] };
    function waveD(b) {
        var top = [], bot = [], x;
        for (x = PLN_WAVES.x0; x <= PLN_WAVES.x1; x += 3) { top.push(x + ' ' + (b[0] + b[1] * Math.sin(2 * Math.PI * x / PLN_WAVES.lam + b[2])).toFixed(1)); }
        for (x = PLN_WAVES.x1; x >= PLN_WAVES.x0; x -= 3) { bot.push(x + ' ' + (b[0] + b[3] + b[1] * Math.sin(2 * Math.PI * x / PLN_WAVES.lam + b[2])).toFixed(1)); }
        return 'M' + top.join(' L') + ' L' + bot.join(' L') + ' Z';
    }

    function rnd(a, b) { return a + Math.random() * (b - a); }
    function add(parent, cls, style) {
        var e = d.createElement('div');
        e.className = cls;
        e.style.cssText = style;
        parent.appendChild(e);
        return e;
    }
    function burst(st, x, y, n, o) {
        o = o || {};
        var cols = o.colors || COLORS;
        for (var i = 0; i < n; i++) {
            var a = rnd(0, 6.283), r = rnd(o.min || 40, o.max || 95), star = Math.random() < (o.stars == null ? .35 : o.stars);
            add(st, 'co-p' + (star ? ' star' : ''),
                'left:' + x + '%;top:' + y + '%;--dx:' + (Math.cos(a) * r).toFixed(1) + 'px;--dy:' + (Math.sin(a) * r).toFixed(1) +
                'px;--c:' + cols[(Math.random() * cols.length) | 0] + ';--s:' + (star ? rnd(8, 13) : rnd(4, 8)).toFixed(1) +
                'px;--t:' + ((o.t || 0) + rnd(0, .08)).toFixed(2) + 's;--d:' + rnd(.7, 1.2).toFixed(2) + 's');
        }
    }
    function ring(st, x, y, c, t, size) {
        add(st, 'co-ring', 'left:' + x + '%;top:' + y + '%;--c:' + c + ';--t:' + t + 's;width:' + size + 'px;height:' + size + 'px;margin:-' + size / 2 + 'px 0 0 -' + size / 2 + 'px');
    }
    function svg(inner) { return '<svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">' + inner + '</svg>'; }

    /* ---------- 6 animasi ---------- */
    var ANIMS = [
        // 1. Logo PLN: gelombang biru muncul, petir merah menyambar, ripple + percikan
        function (st) {
            var waves = '';
            PLN_WAVES.bands.forEach(function (bd, i) {
                waves += '<path class="co1-wave" d="' + waveD(bd) + '" fill="#29A8DE" style="--t:' + (.4 + i * .15).toFixed(2) + 's"/>';
            });
            st.innerHTML = svg(
                '<g transform="translate(25 25)">' +
                '<g class="co1-tile"><rect width="250" height="250" rx="8" fill="#F6E72E"/></g>' + waves +
                '<polygon class="co1-bolt" points="' + PLN_BOLT + '" fill="#E52A2B" stroke="#E52A2B" stroke-width="1" stroke-linejoin="miter"/>' +
                '<rect class="co1-glare" width="250" height="250" rx="8" fill="#fff"/></g>');
            [0, .18, .36].forEach(function (t, i) { ring(st, 50, 50, i % 2 ? '#29A8DE' : '#F5B700', 1.3 + t, 150); });
            burst(st, 50, 50, 20, { t: 1.3, min: 100, max: 160, colors: ['#F5B700', '#29A8DE', '#E52A2B'] });
        },
        // 2. Bola lampu menyala terang
        function (st) {
            var rays = '';
            for (var i = 0; i < 9; i++) {
                var a = (-90 + i * 22.5 - 90) * Math.PI / 180, c = Math.cos(a), s = Math.sin(a);
                rays += '<line class="co2-ray" x1="' + (150 + c * 98).toFixed(1) + '" y1="' + (128 + s * 98).toFixed(1) + '" x2="' + (150 + c * 124).toFixed(1) + '" y2="' + (128 + s * 124).toFixed(1) +
                    '" stroke="#F5B700" stroke-width="6" stroke-linecap="round" style="--rx:' + (c * 12).toFixed(1) + 'px;--ry:' + (s * 12).toFixed(1) + 'px"/>';
            }
            st.innerHTML = svg(
                '<path d="M150 298 V214" stroke="#546e7a" stroke-width="8" stroke-linecap="round" fill="none"/>' +
                '<path class="co2-wire" pathLength="100" d="M150 298 V214" stroke="#FFC400" stroke-width="8" stroke-linecap="round" fill="none"/>' +
                '<g class="co2-bulb">' +
                '<path class="co2-glass" stroke="#90a4ae" stroke-width="4" stroke-linejoin="round" d="M150 66 C112 66 90 98 90 128 C90 152 106 166 116 182 L116 196 L184 196 L184 182 C194 166 210 152 210 128 C210 98 188 66 150 66 Z"/>' +
                '<rect x="118" y="200" width="64" height="12" rx="4" fill="#b0bec5"/><rect x="124" y="216" width="52" height="10" rx="4" fill="#90a4ae"/>' +
                '<path class="co2-fil" d="M134 160 L142 128 L150 150 L158 128 L166 160" fill="none" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</g>' + rays);
            burst(st, 50, 43, 22, { t: 1.45, min: 70, max: 130, colors: ['#FFC400', '#FF9F1C', '#F5B700'], stars: .6 });
        },
        // 3. Colokan listrik tersambung
        function (st) {
            st.innerHTML = svg(
                '<g class="co3-plug"><path d="M20 150 H112" stroke="#90a4ae" stroke-width="10" stroke-linecap="round" fill="none"/>' +
                '<rect x="110" y="118" width="55" height="64" rx="12" fill="#1E73BE"/><rect x="118" y="130" width="8" height="40" rx="4" fill="#fff" opacity=".35"/>' +
                '<rect x="165" y="132" width="26" height="10" rx="3" fill="#78909c"/><rect x="165" y="158" width="26" height="10" rx="3" fill="#78909c"/></g>' +
                '<g class="co3-sock"><path d="M280 150 H236" stroke="#90a4ae" stroke-width="10" stroke-linecap="round" fill="none"/>' +
                '<rect x="182" y="112" width="56" height="76" rx="12" fill="#FFC400"/>' +
                '<rect x="182" y="132" width="12" height="10" fill="#0b2a4a"/><rect x="182" y="158" width="12" height="10" fill="#0b2a4a"/></g>' +
                '<path class="co3-flow" pathLength="100" d="M110 150 H20" stroke="#FFC400" stroke-width="5" stroke-linecap="round" fill="none"/>' +
                '<path class="co3-flow" pathLength="100" d="M238 150 H280" stroke="#FFC400" stroke-width="5" stroke-linecap="round" fill="none"/>' +
                '<g class="co3-check"><circle cx="150" cy="62" r="22" fill="#22c55e"/><path d="M139 62 L148 71 L163 54" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></g>');
            ring(st, 58, 50, '#FFC400', .95, 90);
            burst(st, 58, 50, 20, { t: .95, min: 35, max: 90, colors: ['#1E73BE', '#FFC400', '#29B6F6'] });
        },
        // 4. Baterai terisi penuh
        function (st) {
            var cols = ['#E4262C', '#F57C00', '#FFC400', '#7CB342', '#26C6DA'], bars = '';
            for (var i = 0; i < 5; i++) {
                bars += '<rect class="co4-bar" x="98" y="' + (216 - i * 34) + '" width="104" height="28" rx="5" fill="' + cols[i] + '" style="animation-delay:' + (.45 + i * .22).toFixed(2) + 's"/>';
            }
            st.innerHTML = svg(
                '<defs><clipPath id="co4clip"><rect x="84" y="72" width="132" height="178" rx="8"/></clipPath></defs>' +
                '<rect x="125" y="50" width="50" height="18" rx="5" fill="#1E73BE"/>' +
                '<rect x="80" y="68" width="140" height="186" rx="16" fill="rgba(30,115,190,.08)" stroke="#1E73BE" stroke-width="8"/>' +
                bars +
                '<polygon class="co4-bolt" points="158,118 128,170 148,170 142,212 174,154 154,154" fill="#fff" stroke="#1E73BE" stroke-width="3" stroke-linejoin="round"/>' +
                '<g clip-path="url(#co4clip)"><rect class="co4-shine" x="-40" y="60" width="38" height="200" fill="#fff" opacity=".55"/></g>' +
                '<text id="co4pct" x="150" y="290" text-anchor="middle" font-size="26" font-weight="800" fill="#0b2a4a" font-family="inherit">0%</text>');
            var t0 = Date.now() + 450, el = st.querySelector('#co4pct');
            (function tick() {
                var p = Math.min(1, Math.max(0, (Date.now() - t0) / 1350));
                if (el) el.textContent = Math.round(p * 100) + '%';
                if (p < 1 && el && el.isConnected) requestAnimationFrame(tick);
            })();
            burst(st, 50, 50, 24, { t: 2.05, min: 80, max: 140, colors: ['#FFC400', '#26C6DA', '#1E73BE'], stars: .7 });
        },
        // 5. Petir meluncur jadi roket
        function (st) {
            st.innerHTML = svg(
                '<polygon class="co5-bolt" points="' + BOLT + '" fill="#E4262C" stroke="#fff" stroke-width="3" stroke-linejoin="round"/>' +
                '<g class="co5-rocket"><g class="co5-in">' +
                '<g class="co5-flame"><path d="M136 190 Q150 262 164 190 Z" fill="#FFC400"/><path d="M142 190 Q150 238 158 190 Z" fill="#1E73BE"/></g>' +
                '<path d="M132 166 L108 198 L132 188 Z" fill="#E4262C"/><path d="M168 166 L192 198 L168 188 Z" fill="#E4262C"/>' +
                '<path d="M150 66 C178 98 180 150 172 192 L128 192 C120 150 122 98 150 66 Z" fill="#fff" stroke="#1E73BE" stroke-width="4"/>' +
                '<path d="M150 66 C160 78 167 90 171 104 L129 104 C133 90 140 78 150 66 Z" fill="#E4262C"/>' +
                '<circle cx="150" cy="132" r="14" fill="#1E73BE" stroke="#FFC400" stroke-width="4"/></g></g>');
            for (var s = 0; s < 14; s++) add(st, 'co-twinkle', 'left:' + rnd(2, 98).toFixed(0) + '%;top:' + rnd(2, 98).toFixed(0) + '%;--t:' + rnd(1.2, 2.6).toFixed(2) + 's');
            burst(st, 50, 52, 14, { t: .95, min: 40, max: 90, colors: ['#E4262C', '#FFC400'] });
            // jejak kilatan di belakang roket
            for (var i = 0; i < 26; i++) {
                var prog = i / 25, y = 62 - Math.pow(prog, 2) * 175;
                add(st, 'co-p' + (i % 3 === 0 ? ' star' : ''),
                    'left:' + (50 + rnd(-3, 3)).toFixed(1) + '%;top:' + y.toFixed(1) + '%;--dx:' + rnd(-24, 24).toFixed(0) + 'px;--dy:' + rnd(20, 60).toFixed(0) +
                    'px;--c:' + (i % 2 ? '#FFC400' : '#29B6F6') + ';--s:' + rnd(5, 10).toFixed(1) + 'px;--t:' + (1.35 + prog * 1.1).toFixed(2) + 's;--d:.8s');
            }
        },
        // 6. Jaringan gardu / sirkuit menyala
        function (st) {
            var paths = [
                ['M150 270 V190', .2], ['M150 190 V120', .65], ['M150 190 H70 V150', .75], ['M150 190 H230 V150', .75],
                ['M150 150 H95 V95', 1.1], ['M150 150 H205 V95', 1.1]
            ], lamps = [[70, 150, 1.25], [230, 150, 1.25], [95, 95, 1.6], [205, 95, 1.6]], base = '', flow = '', dots = '';
            paths.forEach(function (p) {
                base += '<path d="' + p[0] + '" stroke="rgba(30,115,190,.22)" stroke-width="5" fill="none" stroke-linejoin="round"/>';
                flow += '<path class="co6-flow" pathLength="100" d="' + p[0] + '" stroke="#FFC400" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="--t:' + p[1] + 's"/>';
            });
            [[150, 190, .65], [150, 150, 1.1]].forEach(function (n) {
                dots += '<circle class="co6-node" cx="' + n[0] + '" cy="' + n[1] + '" r="6" fill="#1E73BE" stroke="#FFC400" stroke-width="3" style="--t:' + n[2] + 's"/>';
            });
            lamps.forEach(function (l) { dots += '<circle class="co6-lamp" cx="' + l[0] + '" cy="' + l[1] + '" r="11" style="--t:' + l[2] + 's"/>'; });
            st.innerHTML = svg(base + flow + dots +
                '<circle cx="150" cy="270" r="10" fill="#1E73BE" stroke="#FFC400" stroke-width="4"/>' +
                '<g class="co6-tower" fill="none" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M150 26 L128 120 M150 26 L172 120 M137 78 H163 M133 98 H167 M141 56 H159 M118 50 H182 M122 50 V62 M178 50 V62"/></g>');
            burst(st, 50, 22, 16, { t: 2.1, min: 40, max: 90, colors: ['#FFC400', '#1E73BE'], stars: .5 });
        }
    ];

    /* ---------- pilihan acak harian ---------- */
    function hashStr(s) {
        var h = 1779033703 ^ s.length;
        for (var i = 0; i < s.length; i++) { h = Math.imul(h ^ s.charCodeAt(i), 3432918353); h = (h << 13) | (h >>> 19); }
        h = Math.imul(h ^ (h >>> 16), 2246822507);
        h = Math.imul(h ^ (h >>> 13), 3266489909);
        return (h ^ (h >>> 16)) >>> 0;
    }
    function dayKey(offset) {
        var t = new Date(); t.setDate(t.getDate() + offset);
        return t.getFullYear() + '-' + (t.getMonth() + 1) + '-' + t.getDate();
    }
    function pickIndex(uid) {
        var n = ANIMS.length, today = hashStr(dayKey(0) + '|' + uid) % n, yest = hashStr(dayKey(-1) + '|' + uid) % n;
        return today === yest ? (today + 1) % n : today;
    }

    function play(opt) {
        opt = opt || {};
        if (d.querySelector('.co-overlay')) return;
        var idx = opt.index != null ? (opt.index | 0) % ANIMS.length : pickIndex(opt.userId || 0);

        var ov = d.createElement('div');
        ov.className = 'co-overlay';
        ov.setAttribute('role', 'status');
        ov.innerHTML = '<div class="co-stage"></div><div class="co-caption"><b>Jam pulang tercatat</b><em></em><span></span></div>';
        ov.querySelector('em').textContent = opt.time || '';
        ov.querySelector('span').textContent = opt.message || 'Terima kasih atas kerja hari ini.';
        d.body.appendChild(ov);

        var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!reduce) ANIMS[idx](ov.querySelector('.co-stage'));

        requestAnimationFrame(function () { ov.classList.add('is-in'); });

        var closed = false;
        function close() {
            if (closed) return; closed = true;
            ov.classList.remove('is-in');
            d.removeEventListener('keydown', onKey);
            setTimeout(function () { if (ov.parentNode) ov.parentNode.removeChild(ov); }, 300);
        }
        function onKey(e) { if (e.key === 'Escape') close(); }
        ov.addEventListener('click', close);
        d.addEventListener('keydown', onKey);
        setTimeout(close, reduce ? 2600 : 5200);
    }

    w.ClockoutCelebration = { play: play, count: ANIMS.length };

    // Pratinjau: ?preview_celebration=1..6
    var m = /[?&]preview_celebration=(\d+)/.exec(w.location.search);
    if (m) {
        var go = function () { play({ index: Math.max(0, parseInt(m[1], 10) - 1), time: '17:00', message: 'Pratinjau animasi ' + m[1] + ' dari ' + ANIMS.length }); };
        d.readyState === 'loading' ? d.addEventListener('DOMContentLoaded', go) : go();
    }
})(window, document);
