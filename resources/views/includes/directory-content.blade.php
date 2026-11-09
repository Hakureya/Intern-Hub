{{-- Isi halaman Direktori Peserta (dipakai di portal peserta dan panel admin/mentor). Data dimuat lewat JS dari route directory.search. --}}
<div id="pcDirectory">
    <div class="pc-dir-tools">
        <div class="pc-search">
            <i class="mdi mdi-magnify pc-search-ic" aria-hidden="true"></i>
            <input type="search" id="pcDirQ" class="pc-search-input" placeholder="Cari nama peserta…" maxlength="60"
                value="{{ $q }}" aria-label="Cari nama peserta" autocomplete="off">
        </div>
        <select id="pcDirDiv" class="pc-dir-select" aria-label="Filter divisi">
            <option value="">Semua divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
            @endforeach
        </select>
        <span class="pc-dir-count" id="pcDirCount"></span>
    </div>

    <div class="pc-dir-grid" id="pcDirGrid"></div>
    <button type="button" class="pc-dir-more" id="pcDirMore" hidden>Muat lebih banyak</button>
</div>
