{{--
    Kartu profil (kiri) yang ikut berubah sesuai isian form. Dipakai di profil admin dan profil peserta.

    Parameter:
      $formId     id <form> yang memuat input foto (input file & hapus foto dikaitkan lewat atribut form="...")
      $ids        id input form: ['name' => ..., 'email' => ..., 'division' => ...]
      $name, $email, $roleLabel, $division (teks), $photoUrl (boleh null)
      $stickyTop  (opsional) jarak kartu dari atas layar saat di-scroll, default 90px (di bawah topbar admin)
      $idCard     (opsional) array ['logo' => url, 'discoverable' => bool, 'photoVisible' => bool, 'fields' => [...]].
                  Jika diisi, kartu tampil sebagai pratinjau kartu peserta (apa yang dilihat peserta lain),
                  lengkap dengan centang "bisa ditemukan" dan "tampilkan foto". Tanpa ini = tampilan lama.
--}}
<style>
    .pfc {
        padding: 24px 20px;
        text-align: center;
        background: #fff;
        border: 1px solid #e3ebf1;
        border-radius: 12px;
    }

    @media (min-width: 992px) {
        .pfc {
            position: sticky;
            top: var(--pfc-top, 90px);
        }
    }

    .pfc [hidden],
    .pfc-modal[hidden] {
        display: none !important;
    }

    /* Foto tampil lingkaran kecil (hasil crop tetap 1 : 1) */
    .pfc-photo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 96px;
        margin: 0 auto 16px;
        overflow: hidden;
        border-radius: 50%;
        background: #dcedf8;
        color: #1b6aa8;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: .02em;
        box-shadow: 0 0 0 6px #e8f3fa;
    }

    .pfc-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .pfc-name {
        margin: 0 0 4px;
        font-size: 20px;
        font-weight: 700;
        word-break: break-word;
    }

    .pfc-email {
        margin: 0 0 14px;
        color: #6b7c8c;
        font-size: 14px;
        word-break: break-all;
    }

    .pfc-role {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 20px;
        background: #e3f2fa;
        color: #1b6aa8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .pfc-division {
        margin: 14px 0 18px;
        color: #6b7c8c;
        font-size: 13px;
    }

    .pfc-division strong {
        color: #2d3b48;
    }

    .pfc-actions {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pfc-btn {
        padding: 8px 14px;
        border: 1px solid #c4dcec;
        border-radius: 8px;
        background: #fff;
        color: #1b6aa8;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
    }

    .pfc-btn:hover:not(:disabled) {
        background: #e8f3fa;
    }

    .pfc-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .pfc-btn-primary {
        border-color: #1b6aa8;
        background: #1b6aa8;
        color: #fff;
    }

    .pfc-btn-primary:hover:not(:disabled) {
        background: #134f82;
    }

    .pfc-hint {
        display: block;
        margin-top: 14px;
        color: #8a98a5;
        font-size: 12px;
    }

    .pfc-error {
        margin-top: 10px;
        color: #d64545;
        font-size: 12px;
    }

    /* Editor crop */
    .pfc-modal {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(15, 42, 67, .55);
    }

    .pfc-modal-box {
        width: 100%;
        max-width: 400px;
        padding: 22px;
        border-radius: 14px;
        background: #fff;
        text-align: center;
        box-shadow: 0 25px 65px #0f2a4340;
    }

    .pfc-modal-box h5 {
        margin: 0 0 4px;
        font-size: 18px;
        font-weight: 700;
    }

    .pfc-modal-sub {
        margin: 0 0 14px;
        color: #6b7c8c;
        font-size: 13px;
    }

    .pfc-stage {
        position: relative;
        width: min(320px, 100%);
        aspect-ratio: 1 / 1;
        margin: 0 auto 14px;
        overflow: hidden;
        border-radius: 8px;
        background: #0f2a43;
        cursor: grab;
        touch-action: none;
        user-select: none;
    }

    .pfc-stage.is-dragging {
        cursor: grabbing;
    }

    .pfc-stage img {
        position: absolute;
        top: 0;
        left: 0;
        max-width: none !important;
        transform-origin: 0 0;
        pointer-events: none;
    }

    .pfc-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
        border: 2px solid #ffffffcc;
        background:
            linear-gradient(#ffffff55, #ffffff55) 33.33% 0 / 1px 100% no-repeat,
            linear-gradient(#ffffff55, #ffffff55) 66.66% 0 / 1px 100% no-repeat,
            linear-gradient(#ffffff55, #ffffff55) 0 33.33% / 100% 1px no-repeat,
            linear-gradient(#ffffff55, #ffffff55) 0 66.66% / 100% 1px no-repeat;
    }

    .pfc-zoom {
        display: flex;
        align-items: center;
        gap: 10px;
        width: min(320px, 100%);
        margin: 0 auto 16px;
        color: #6b7c8c;
        font-size: 12px;
    }

    .pfc-zoom input[type=range] {
        flex: 1 1 auto;
    }

    .pfc-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
</style>

@if (empty($idCard))
<div class="pfc" style="--pfc-top: {{ $stickyTop ?? '90px' }};">
    <div class="pfc-photo" aria-hidden="true">
        <img id="pfcImg" alt="Foto profil" @if ($photoUrl) src="{{ $photoUrl }}" @else hidden @endif>
        <span id="pfcInitials" @if ($photoUrl) hidden @endif>-</span>
    </div>

    <h4 class="pfc-name" id="pfcName">{{ $name }}</h4>
    <p class="pfc-email" id="pfcEmail">{{ $email }}</p>
    <span class="pfc-role">{{ $roleLabel }}</span>
    <p class="pfc-division">Divisi: <strong id="pfcDivision">{{ $division ?: '-' }}</strong></p>

    <div class="pfc-actions">
        <button type="button" class="pfc-btn" id="pfcUpload">Unggah foto</button>
        <button type="button" class="pfc-btn" id="pfcEdit" @if (!$photoUrl) disabled @endif>Edit foto</button>
        <button type="button" class="pfc-btn" id="pfcRemove" @if (!$photoUrl) disabled @endif>Hapus foto</button>
    </div>
    <small class="pfc-hint">JPG, PNG, atau WebP. Maksimal 10 MB. Foto dipotong 1 : 1.</small>
    <div class="pfc-error" id="pfcError" hidden></div>

    <input type="file" id="pfcFile" name="profile_photo" form="{{ $formId }}" accept=".jpg,.jpeg,.png,.webp"
        hidden>
    <input type="hidden" id="pfcRemoveFlag" name="remove_photo" value="0" form="{{ $formId }}">
</div>
@else
<div class="pfc pfc--id" style="--pfc-top: {{ $stickyTop ?? '90px' }};">
    <div class="pc-fit pc-fit--profile">
    <div class="pc-id" id="pcPreviewCard">
        <div class="pc-id-top">
            <img class="pc-id-logo" src="{{ $idCard['logo'] }}" alt="PLN Suku Cadang">
            <span class="pc-id-badge">Intern</span>
        </div>
        <div class="pc-id-photo{{ empty($idCard['photoVisible']) ? ' is-off' : '' }}" id="pcPhotoBox">
            <img id="pfcImg" alt="Foto profil" @if ($photoUrl) src="{{ $photoUrl }}" @else hidden @endif>
            <span class="pc-id-initials" id="pfcInitials" @if ($photoUrl) hidden @endif>-</span>
            <span class="pc-id-initials pc-id-off" id="pcInitialsOff">-</span>
        </div>
        <div class="pc-id-name"><i>&#10022;</i><span id="pfcName">{{ $name }}</span><i>&#10022;</i></div>
        <ul class="pc-id-info" id="pcInfo"></ul>
        <div class="pc-id-foot">Learn &middot; Contribute &middot; Grow</div>
    </div>
    </div>
    <p class="pfc-preview-note">Pratinjau langsung: begini kartumu terlihat oleh peserta lain.</p>

    <div class="pfc-actions">
        <button type="button" class="pfc-btn" id="pfcUpload">Unggah foto</button>
        <button type="button" class="pfc-btn" id="pfcEdit" @if (!$photoUrl) disabled @endif>Edit foto</button>
        <button type="button" class="pfc-btn" id="pfcRemove" @if (!$photoUrl) disabled @endif>Hapus foto</button>
    </div>
    <small class="pfc-hint">JPG, PNG, atau WebP. Maksimal 10 MB. Foto dipotong 1 : 1.</small>
    <div class="pfc-error" id="pfcError" hidden></div>

    <div class="pfc-opts">
        <input type="hidden" name="card_visibility[photo]" value="0" form="{{ $formId }}">
        <label class="pc-check">
            <input type="checkbox" name="card_visibility[photo]" value="1" form="{{ $formId }}" id="pcShowPhoto"
                data-pc-show="photo" {{ !empty($idCard['photoVisible']) ? 'checked' : '' }}>
            <span>Tampilkan foto di kartu</span>
        </label>
        <input type="hidden" name="is_discoverable" value="0" form="{{ $formId }}">
        <label class="pc-check">
            <input type="checkbox" name="is_discoverable" value="1" form="{{ $formId }}" id="pcDiscoverable"
                {{ !empty($idCard['discoverable']) ? 'checked' : '' }}>
            <span>Izinkan peserta lain menemukan saya
                <small>Jika dimatikan, kartumu tidak muncul di pencarian maupun Direktori Peserta.</small></span>
        </label>
    </div>

    <input type="file" id="pfcFile" name="profile_photo" form="{{ $formId }}" accept=".jpg,.jpeg,.png,.webp"
        hidden>
    <input type="hidden" id="pfcRemoveFlag" name="remove_photo" value="0" form="{{ $formId }}">
</div>
@endif

{{-- Editor crop 1 : 1 --}}
<div class="pfc-modal" id="pfcModal" hidden role="dialog" aria-modal="true" aria-label="Atur foto profil">
    <div class="pfc-modal-box">
        <h5>Atur foto profil</h5>
        <p class="pfc-modal-sub">Geser foto dan atur zoom untuk memilih bagian yang ditampilkan (1 : 1).</p>
        <div class="pfc-stage" id="pfcStage">
            <img id="pfcCropImg" alt="" draggable="false">
            <div class="pfc-grid"></div>
        </div>
        <div class="pfc-zoom">
            <span>Zoom</span>
            <input type="range" id="pfcZoom" min="1" max="4" step="0.01" value="1" aria-label="Zoom">
        </div>
        <div class="pfc-error" id="pfcCropError" hidden></div>
        <div class="pfc-modal-actions">
            <button type="button" class="pfc-btn" id="pfcCropCancel">Batal</button>
            <button type="button" class="pfc-btn pfc-btn-primary" id="pfcCropApply">Terapkan</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var IDS = @json($ids);
        var MAX_BYTES = 10 * 1024 * 1024;
        var TYPES = ['image/jpeg', 'image/png', 'image/webp'];
        var OUTPUT_MAX = 800;

        var nameInput = document.getElementById(IDS.name);
        var emailInput = document.getElementById(IDS.email);
        var divisionSelect = document.getElementById(IDS.division);

        var img = document.getElementById('pfcImg');
        var initialsEl = document.getElementById('pfcInitials');
        var fileInput = document.getElementById('pfcFile');
        var removeFlag = document.getElementById('pfcRemoveFlag');
        var editBtn = document.getElementById('pfcEdit');
        var removeBtn = document.getElementById('pfcRemove');
        var errorEl = document.getElementById('pfcError');

        var modal = document.getElementById('pfcModal');
        var stage = document.getElementById('pfcStage');
        var cropImg = document.getElementById('pfcCropImg');
        var zoomInput = document.getElementById('pfcZoom');
        var cropError = document.getElementById('pfcCropError');
        var applyBtn = document.getElementById('pfcCropApply');

        // Pindahkan modal ke <body> agar tidak terpengaruh posisi sticky kartu.
        document.body.appendChild(modal);

        var hasPhoto = !!img.getAttribute('src');
        var editSource = hasPhoto ? img.getAttribute('src') : null; // sumber untuk tombol "Edit foto"
        var editSourceIsBlob = false;
        var previewUrl = null; // object URL pratinjau hasil crop
        var appliedFile = null; // hasil crop yang akan dikirim
        var pendingUrl = null; // object URL file yang baru dipilih (belum di-crop)
        var pendingIsNew = false;

        // Status editor
        var V = 0,
            natW = 0,
            natH = 0,
            minS = 1,
            s = 1,
            posX = 0,
            posY = 0;

        function initials(name) {
            var parts = name.trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return '-';
            var first = parts[0].charAt(0);
            var last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
            return (first + last).toUpperCase();
        }

        function renderText() {
            var name = nameInput ? nameInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            var division = '-';

            if (divisionSelect && divisionSelect.value) {
                division = divisionSelect.options[divisionSelect.selectedIndex].text.trim() || '-';
            }

            // Tampilan kartu ID tidak punya elemen email/divisi terpisah, jadi semuanya dicek dulu.
            var nameEl = document.getElementById('pfcName');
            var emailEl = document.getElementById('pfcEmail');
            var divisionEl = document.getElementById('pfcDivision');
            if (nameEl) nameEl.textContent = name || '-';
            if (emailEl) emailEl.textContent = email || '-';
            if (divisionEl) divisionEl.textContent = division;
            initialsEl.textContent = initials(name);
        }

        function renderPhoto() {
            img.hidden = !hasPhoto;
            initialsEl.hidden = hasPhoto;
            editBtn.disabled = !hasPhoto;
            removeBtn.disabled = !hasPhoto;
        }

        function showError(message) {
            errorEl.textContent = message || '';
            errorEl.hidden = !message;
        }

        function showCropError(message) {
            cropError.textContent = message || '';
            cropError.hidden = !message;
        }

        function setInputFile(file) {
            try {
                var dt = new DataTransfer();
                if (file) dt.items.add(file);
                fileInput.files = dt.files;
                return true;
            } catch (e) {
                if (!file) fileInput.value = '';
                return false;
            }
        }

        /* ---------- Editor crop ---------- */
        function clamp() {
            var w = natW * s,
                h = natH * s;
            posX = Math.min(0, Math.max(V - w, posX));
            posY = Math.min(0, Math.max(V - h, posY));
        }

        function renderCrop() {
            cropImg.style.transform = 'translate(' + posX + 'px,' + posY + 'px) scale(' + s + ')';
        }

        function setZoom(z, keepCenter) {
            z = Math.min(4, Math.max(1, z));
            var cx = (V / 2 - posX) / s;
            var cy = (V / 2 - posY) / s;
            s = minS * z;
            if (keepCenter !== false) {
                posX = V / 2 - cx * s;
                posY = V / 2 - cy * s;
            }
            zoomInput.value = z;
            clamp();
            renderCrop();
        }

        function openCrop(src, isNew) {
            pendingIsNew = isNew;
            showCropError('');
            applyBtn.disabled = true;
            modal.hidden = false;

            cropImg.onload = function() {
                natW = cropImg.naturalWidth;
                natH = cropImg.naturalHeight;
                cropImg.style.width = natW + 'px';
                cropImg.style.height = natH + 'px';
                V = stage.clientWidth;
                minS = V / Math.min(natW, natH);
                s = minS;
                posX = (V - natW * s) / 2;
                posY = (V - natH * s) / 2;
                zoomInput.value = 1;
                clamp();
                renderCrop();
                applyBtn.disabled = false;
            };
            cropImg.onerror = function() {
                showCropError('Foto tidak dapat dimuat. Silakan unggah ulang fotonya.');
            };

            if (src.indexOf('blob:') === 0 || src.indexOf('data:') === 0) {
                cropImg.removeAttribute('crossorigin');
            } else {
                cropImg.crossOrigin = 'anonymous';
            }
            cropImg.removeAttribute('src');
            cropImg.src = src;
        }

        function closeCrop() {
            modal.hidden = true;
            cropImg.onload = null;
            cropImg.onerror = null;
            cropImg.removeAttribute('src');
        }

        function cancelCrop() {
            if (pendingIsNew) {
                // Kembalikan input ke hasil crop sebelumnya (atau kosongkan).
                setInputFile(appliedFile);
                if (pendingUrl) URL.revokeObjectURL(pendingUrl);
                pendingUrl = null;
            }
            closeCrop();
        }

        function applyCrop() {
            var size = V / s;
            var out = Math.max(1, Math.min(OUTPUT_MAX, Math.round(size)));
            var canvas = document.createElement('canvas');
            canvas.width = out;
            canvas.height = out;

            try {
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, out, out);
                ctx.drawImage(cropImg, -posX / s, -posY / s, size, size, 0, 0, out, out);

                canvas.toBlob(function(blob) {
                    if (!blob) {
                        showCropError('Gagal memproses foto. Coba foto lain.');
                        return;
                    }

                    var file = new File([blob], 'profile-photo.jpg', {
                        type: 'image/jpeg'
                    });

                    if (!setInputFile(file)) {
                        showCropError('Browser ini belum mendukung pemotongan foto. Gunakan browser terbaru.');
                        return;
                    }

                    appliedFile = file;
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                    previewUrl = URL.createObjectURL(file);
                    img.src = previewUrl;
                    hasPhoto = true;
                    removeFlag.value = '0';

                    // Sumber "Edit foto" berikutnya = foto asli yang baru dipilih.
                    if (pendingIsNew && pendingUrl) {
                        if (editSourceIsBlob && editSource) URL.revokeObjectURL(editSource);
                        editSource = pendingUrl;
                        editSourceIsBlob = true;
                        pendingUrl = null;
                    }

                    showError('');
                    renderPhoto();
                    closeCrop();
                }, 'image/jpeg', 0.9);
            } catch (e) {
                showCropError('Foto ini tidak dapat diedit. Silakan unggah ulang fotonya.');
            }
        }

        // Geser (drag)
        var drag = null;
        stage.addEventListener('pointerdown', function(e) {
            drag = {
                x: e.clientX,
                y: e.clientY,
                px: posX,
                py: posY
            };
            stage.classList.add('is-dragging');
            stage.setPointerCapture(e.pointerId);
        });
        stage.addEventListener('pointermove', function(e) {
            if (!drag) return;
            posX = drag.px + (e.clientX - drag.x);
            posY = drag.py + (e.clientY - drag.y);
            clamp();
            renderCrop();
        });
        ['pointerup', 'pointercancel'].forEach(function(type) {
            stage.addEventListener(type, function() {
                drag = null;
                stage.classList.remove('is-dragging');
            });
        });

        // Zoom
        zoomInput.addEventListener('input', function() {
            setZoom(parseFloat(zoomInput.value));
        });
        stage.addEventListener('wheel', function(e) {
            e.preventDefault();
            setZoom(parseFloat(zoomInput.value) - e.deltaY * 0.002);
        }, {
            passive: false
        });

        document.getElementById('pfcCropCancel').addEventListener('click', cancelCrop);
        applyBtn.addEventListener('click', applyCrop);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.hidden) cancelCrop();
        });

        /* ---------- Tombol kartu ---------- */
        document.getElementById('pfcUpload').addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            var file = fileInput.files && fileInput.files[0];
            showError('');
            if (!file) return;

            if (TYPES.indexOf(file.type) === -1) {
                setInputFile(appliedFile);
                showError('Format foto harus JPG, PNG, atau WebP.');
                return;
            }
            if (file.size > MAX_BYTES) {
                setInputFile(appliedFile);
                showError('Ukuran foto maksimal 10 MB.');
                return;
            }

            if (pendingUrl) URL.revokeObjectURL(pendingUrl);
            pendingUrl = URL.createObjectURL(file);
            openCrop(pendingUrl, true);
        });

        editBtn.addEventListener('click', function() {
            if (editSource) openCrop(editSource, false);
        });

        removeBtn.addEventListener('click', function() {
            setInputFile(null);
            appliedFile = null;
            if (editSourceIsBlob && editSource) URL.revokeObjectURL(editSource);
            editSource = null;
            editSourceIsBlob = false;
            img.removeAttribute('src');
            hasPhoto = false;
            removeFlag.value = '1';
            showError('');
            renderPhoto();
        });

        [nameInput, emailInput].forEach(function(el) {
            if (el) el.addEventListener('input', renderText);
        });
        if (divisionSelect) divisionSelect.addEventListener('change', renderText);

        renderText();
        renderPhoto();
    });
</script>

@if (!empty($idCard))
<script>
    // Pratinjau langsung: baris info kartu mengikuti isian form + centang "tampilkan di kartu".
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.PeopleCard) return;

        var F = @json($idCard['fields']);
        var info = document.getElementById('pcInfo');
        var photoBox = document.getElementById('pcPhotoBox');
        var offInitials = document.getElementById('pcInitialsOff');
        var form = document.getElementById(@json($formId));
        var checks = [].slice.call(document.querySelectorAll('[data-pc-show]'));

        function el(key) { return F[key] ? document.getElementById(F[key]) : null; }
        function text(key) { var e = el(key); return e ? String(e.value || '').trim() : ''; }
        function isShown(key) {
            return checks.some(function(c) { return c.getAttribute('data-pc-show') === key && c.checked; });
        }

        function build() {
            var divSel = el('division');
            var mentor = text('mentor');
            var s = PeopleCard.fmtDate(text('start'));
            var e = PeopleCard.fmtDate(text('end'));
            var values = {
                division: divSel && divSel.value ? divSel.options[divSel.selectedIndex].text.trim() : '',
                mentor: mentor === '-' ? '' : mentor,
                major: text('major'),
                institution: text('institution'),
                period: (s || e) ? (s || '?') + ' – ' + (e || '?') : '',
                email: text('email'),
                phone_number: text('phone_number'),
                address: text('address'),
                birth_date: PeopleCard.fmtDate(text('birth_date'))
            };

            var rows = PeopleCard.FIELDS.filter(function(f) {
                return isShown(f.k) && values[f.k];
            }).map(function(f) {
                return { k: f.k, label: f.label, value: values[f.k] };
            });

            info.innerHTML = PeopleCard.rowsHtml(rows);
            photoBox.classList.toggle('is-off', !isShown('photo'));
            offInitials.textContent = PeopleCard.initialsOf(text('name'));
        }

        // Dua centang periode (mulai & selesai) selalu sama.
        checks.forEach(function(c) {
            c.addEventListener('change', function() {
                var key = c.getAttribute('data-pc-show');
                checks.forEach(function(o) {
                    if (o !== c && o.getAttribute('data-pc-show') === key) o.checked = c.checked;
                });
                build();
            });
        });

        if (form) {
            form.addEventListener('input', build);
            // setTimeout: nama mentor diisi skrip lain saat divisi berubah.
            form.addEventListener('change', function() { setTimeout(build, 0); });
        }
        build();
    });
</script>
@endif
