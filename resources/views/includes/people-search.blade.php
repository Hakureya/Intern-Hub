{{-- Kolom cari peserta di navbar atas (dipakai di navbar admin/mentor dan portal peserta). --}}
<form class="pc-search" action="{{ route('directory.index') }}" method="GET" role="search" autocomplete="off"
    data-search-url="{{ route('directory.search') }}" data-directory-url="{{ route('directory.index') }}"
    data-logo="{{ asset('assets/images/logo-plnsc.png') }}">
    <i class="mdi mdi-magnify pc-search-ic" aria-hidden="true"></i>
    <input type="search" name="q" class="pc-search-input" placeholder="Cari peserta…" maxlength="60"
        aria-label="Cari nama peserta magang">
    <div class="pc-search-results" hidden></div>
</form>
