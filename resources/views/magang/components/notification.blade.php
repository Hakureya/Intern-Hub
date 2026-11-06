@if (session('success'))
    <div class="cs-pub-alert is-success">
        <div class="animate-fade-in-up">
            <span class="cs-pub-alert-icon">✓</span>
            <div>
                <strong>Berhasil!</strong>
                {{ session('success') }}
            </div>
        </div>
    </div>
@endif

@if (session('error') || $errors->any())
    <div class="cs-pub-alert is-error">
        <div class="animate-fade-in-up">
            <span class="cs-pub-alert-icon">!</span>
            <div>
                <strong>Terjadi Kesalahan!</strong>
                {{ session('error') ?? 'Pastikan data yang diisi benar dan format file sesuai (PDF).' }}
            </div>
        </div>
    </div>
@endif
