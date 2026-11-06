@extends('magang.layout.app')

@section('title', 'Formulir Pendaftaran Magang')

@section('content')
    <div class="min-h-screen py-6 sm:py-10 px-3 sm:px-4 md:px-0">
        <div class="container mx-auto max-w-3xl">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-8 py-7 relative overflow-hidden" style="background:#e3f2fa;">
                    <div class="absolute top-0 right-0 w-32 h-32 rounded-bl-full" style="background:#a7e8ea;opacity:.55;"></div>
                    <div class="cs-eyebrow relative z-10">Pendaftaran Magang</div>
                    <h1 class="text-2xl font-extrabold relative z-10 font-poppins text-slate-900" style="letter-spacing:-1px;">Formulir Pendaftaran Magang</h1>
                    <p class="text-slate-500 text-sm mt-1 relative z-10">Lengkapi data diri dan berkas Anda dengan benar.</p>
                </div>

                <div class="p-4 sm:p-6 md:p-8">
                    @if ($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-600 rounded-lg p-4 text-sm">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('magang.submit') }}" method="POST" enctype="multipart/form-data"
                        class="space-y-6">
                        @csrf

                        <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                            <label class="block text-sm font-bold text-slate-700 mb-3">Pilih Jenis Magang <span
                                    class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="jenis_magang" value="fresh_graduate" class="peer sr-only"
                                        onclick="toggleFormType('fg')"
                                        {{ old('jenis_magang') == 'fresh_graduate' ? 'checked' : '' }}>
                                    <div
                                        class="p-4 bg-white border-2 border-slate-200 rounded-xl hover:border-pln-green peer-checked:border-pln-green peer-checked:bg-blue-50 transition text-center group">
                                        <span class="text-2xl mb-2 block group-hover:scale-110 transition">🎓</span>
                                        <span class="font-bold text-slate-700 peer-checked:text-pln-green block">Fresh
                                            Graduate</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" name="jenis_magang" value="mahasiswa" class="peer sr-only"
                                        onclick="toggleFormType('mhs')"
                                        {{ old('jenis_magang') == 'mahasiswa' ? 'checked' : '' }}>
                                    <div
                                        class="p-4 bg-white border-2 border-slate-200 rounded-xl hover:border-pln-green peer-checked:border-pln-green peer-checked:bg-blue-50 transition text-center group">
                                        <span class="text-2xl mb-2 block group-hover:scale-110 transition">🏫</span>
                                        <span class="font-bold text-slate-700 peer-checked:text-pln-green block">Mahasiswa
                                            Aktif</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="field_tipe_mahasiswa"
                            class="hidden bg-indigo-50/50 p-5 rounded-xl border border-indigo-100 mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-3">Kategori Kegiatan <span
                                    class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="tipe_mahasiswa" value="pkl" id="input_tipe_pkl"
                                        class="peer sr-only" {{ old('tipe_mahasiswa') == 'pkl' ? 'checked' : '' }}>
                                    <div
                                        class="p-3 bg-white border-2 border-slate-200 rounded-xl hover:border-indigo-400 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition text-center">
                                        <span
                                            class="font-bold text-slate-700 peer-checked:text-indigo-700 block text-sm">PKL
                                            / Magang Wajib</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" name="tipe_mahasiswa" value="riset" id="input_tipe_riset"
                                        class="peer sr-only" {{ old('tipe_mahasiswa') == 'riset' ? 'checked' : '' }}>
                                    <div
                                        class="p-3 bg-white border-2 border-slate-200 rounded-xl hover:border-indigo-400 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition text-center">
                                        <span
                                            class="font-bold text-slate-700 peer-checked:text-indigo-700 block text-sm">Riset
                                            / Penelitian </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Data Personal</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Nama Lengkap <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="nama" value="{{ old('nama') }}" required
                                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Nomor HP (WhatsApp) <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="no_hp" value="{{ old('no_hp') }}" required
                                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                                    <p class="text-[10px] text-slate-400 mt-1">Pastikan nomor aktif WhatsApp. Format:
                                        0812... / 62812...</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Alamat Email <span
                                        class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Alamat Domisili <span
                                        class="text-red-500">*</span></label>
                                <textarea name="alamat" rows="2" required
                                    class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">{{ old('alamat') }}</textarea>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Data Akademik</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Asal Kampus / Universitas
                                        <span class="text-red-500">*</span></label>
                                    <input type="text" name="asal_kampus" value="{{ old('asal_kampus') }}" required
                                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Jurusan / Prodi <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="jurusan" value="{{ old('jurusan') }}" required
                                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div id="field_semester" class="hidden">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Semester Saat Ini <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="semester" value="{{ old('semester') }}"
                                        id="input_semester"
                                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">IPK Terakhir <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" name="ipk" value="{{ old('ipk') }}"
                                        required
                                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition">
                                    <p class="text-[10px] text-slate-400 mt-1">Gunakan Koma (Contoh: 3,75)</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Rencana Periode Magang <span
                                        class="text-red-500">*</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <input type="date" name="tgl_mulai" value="{{ old('tgl_mulai') }}" required
                                            class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition text-slate-600">
                                        <p class="text-[10px] text-slate-400 mt-1">Tanggal Mulai</p>
                                    </div>
                                    <div>
                                        <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}"
                                            required
                                            class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pln-green focus:outline-none transition text-slate-600">
                                        <p class="text-[10px] text-slate-400 mt-1">Tanggal Selesai</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Berkas Persyaratan</h3>
                            <div class="bg-yellow-50 p-3 rounded-lg text-xs text-yellow-700 border border-yellow-200 mb-4">
                                ⚠️ Format file wajib <strong>PDF</strong> dengan ukuran maksimal <strong>2MB</strong> per
                                file.
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Surat Permohonan Magang
                                        <span class="text-red-500">*</span></label>
                                    <input type="file" name="surat_permohonan" required accept=".pdf"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-pln-green hover:file:bg-blue-100">
                                    <p class="text-[10px] text-slate-400 mt-1">Surat pribadi pengajuan magang.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Curriculum Vitae (CV) <span
                                            class="text-red-500">*</span></label>
                                    <input type="file" name="cv" required accept=".pdf"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-pln-green hover:file:bg-blue-100">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Transkrip Nilai Terakhir
                                        <span class="text-red-500">*</span></label>
                                    <input type="file" name="transkrip" required accept=".pdf"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-pln-green hover:file:bg-blue-100">
                                </div>
                            </div>

                            <div id="field_surat_kampus" class="hidden">
                                <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl mt-2">
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Surat Pengantar Kampus <span
                                            class="text-red-500">*</span></label>
                                    <input type="file" name="surat_pengantar" id="input_surat_pengantar"
                                        accept=".pdf"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                    <p class="text-[10px] text-slate-500 mt-1">Wajib bagi Mahasiswa Aktif (PKL/Riset).</p>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Dokumen Pendukung
                                    (Opsional)</label>
                                <p class="text-[10px] text-slate-400 mb-3">Contoh: Sertifikat Organisasi, Portofolio.
                                    Format PDF max 5MB per file.</p>

                                <div id="dokumen_pendukung_wrapper" class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <input type="file" name="dokumen_pendukung[]" accept=".pdf"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                                    </div>
                                </div>

                                <button type="button" onclick="tambahDokumen()"
                                    class="mt-4 bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pln-green" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah File Lainnya
                                </button>
                            </div>

                        </div>

                        <div class="pt-6">
                            <button type="submit"
                                class="w-full bg-pln-green hover:bg-pln-dark text-white font-bold py-4 rounded-xl shadow-lg shadow-pln-green/25 transition transform hover:-translate-y-1">
                                Kirim Pendaftaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFormType(type) {
            const fieldSemester = document.getElementById('field_semester');
            const inputSemester = document.getElementById('input_semester');
            const fieldSuratKampus = document.getElementById('field_surat_kampus');
            const inputSuratKampus = document.getElementById('input_surat_pengantar');

            // Element Kategori Mahasiswa Baru
            const fieldTipeMhs = document.getElementById('field_tipe_mahasiswa');
            const inputTipePkl = document.getElementById('input_tipe_pkl');
            const inputTipeRiset = document.getElementById('input_tipe_riset');

            if (type === 'mhs') {
                fieldSemester.classList.remove('hidden');
                fieldSuratKampus.classList.remove('hidden');
                fieldTipeMhs.classList.remove('hidden');

                inputSemester.setAttribute('required', 'required');
                inputSuratKampus.setAttribute('required', 'required');
                inputTipePkl.setAttribute('required', 'required');
            } else {
                fieldSemester.classList.add('hidden');
                fieldSuratKampus.classList.add('hidden');
                fieldTipeMhs.classList.add('hidden');

                inputSemester.removeAttribute('required');
                inputSemester.value = '';

                inputSuratKampus.removeAttribute('required');
                inputSuratKampus.value = '';

                inputTipePkl.removeAttribute('required');
                inputTipePkl.checked = false;
                inputTipeRiset.checked = false;
            }
        }

        // Fungsi untuk menambah baris input Dokumen Pendukung
        function tambahDokumen() {
            const wrapper = document.getElementById('dokumen_pendukung_wrapper');

            // Batasi maksimal 5 file tambahan
            if (wrapper.children.length >= 5) {
                alert('Maksimal 5 dokumen pendukung yang diizinkan.');
                return;
            }

            const newDiv = document.createElement('div');
            newDiv.className = 'flex items-center gap-2';

            // Tombol hapus file (X)
            newDiv.innerHTML = `
            <input type="file" name="dokumen_pendukung[]" accept=".pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
            <button type="button" onclick="this.parentElement.remove()" class="bg-red-50 text-red-500 hover:bg-red-100 p-2 rounded-lg font-bold transition" title="Hapus File">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        `;

            wrapper.appendChild(newDiv);
        }

        // Jalankan otomatis saat halaman dimuat
        document.addEventListener("DOMContentLoaded", function() {
            const oldType = "{{ old('jenis_magang') }}";
            if (oldType === 'mahasiswa') {
                toggleFormType('mhs');
            } else {
                toggleFormType('fg');
            }
        });
    </script>
@endsection
