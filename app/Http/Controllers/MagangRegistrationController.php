<?php

namespace App\Http\Controllers;

use App\Models\Pendaftar;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MagangRegistrationController extends Controller
{
    public function landing()
    {
        return view('magang.landing');
    }

    public function form()
    {
        return view('magang.form');
    }

    public function status()
    {
        return view('magang.status');
    }

    public function submit(Request $request)
    {
        $rules = [
            'jenis_magang' => 'required|in:fresh_graduate,mahasiswa',
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pendaftars,email',
            'no_hp' => 'required|numeric',
            'alamat' => 'required|string',
            'asal_kampus' => 'required|string',
            'jurusan' => 'required|string',
            'ipk' => 'required|numeric|min:0|max:4',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after:tgl_mulai',
            'cv' => 'required|mimes:pdf|max:2048',
            'transkrip' => 'required|mimes:pdf|max:2048',
            'surat_permohonan' => 'required|mimes:pdf|max:2048',
            'dokumen_pendukung' => 'nullable|array|max:5',
            'dokumen_pendukung.*' => 'mimes:pdf|max:5120',
        ];

        if ($request->jenis_magang === 'mahasiswa') {
            $rules['semester'] = 'required|integer';
            $rules['surat_pengantar'] = 'required|mimes:pdf|max:2048';
            $rules['tipe_mahasiswa'] = 'required|in:pkl,riset';
        }

        $request->validate($rules);

        Carbon::setLocale('id');
        $start = Carbon::parse($request->tgl_mulai)->translatedFormat('d F Y');
        $end = Carbon::parse($request->tgl_selesai)->translatedFormat('d F Y');
        $periodeString = $start . ' s/d ' . $end;

        $cvFile = $request->file('cv');
        $transkripFile = $request->file('transkrip');
        $suratPermohonanFile = $request->file('surat_permohonan');

        $cvPath = $cvFile->store('dokumen_magang', 'public');
        $transkripPath = $transkripFile->store('dokumen_magang', 'public');
        $suratPermohonanPath = $suratPermohonanFile->store('dokumen_magang', 'public');

        $suratPengantarPath = null;
        $suratPengantarName = null;
        if ($request->hasFile('surat_pengantar')) {
            $suratPengantarFile = $request->file('surat_pengantar');
            $suratPengantarPath = $suratPengantarFile->store('dokumen_magang', 'public');
            $suratPengantarName = $suratPengantarFile->getClientOriginalName();
        }

        $dokumenPendukungPaths = [];
        $dokumenPendukungNames = [];
        if ($request->hasFile('dokumen_pendukung')) {
            foreach ($request->file('dokumen_pendukung') as $file) {
                $dokumenPendukungPaths[] = $file->store('dokumen_magang', 'public');
                $dokumenPendukungNames[] = $file->getClientOriginalName();
            }
        }

        Pendaftar::create([
            'jenis_magang' => $request->jenis_magang,
            'tipe_mahasiswa' => $request->jenis_magang === 'mahasiswa' ? $request->tipe_mahasiswa : null,
            'nama' => $request->nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'asal_kampus' => $request->asal_kampus,
            'jurusan' => $request->jurusan,
            'ipk' => $request->ipk,
            'semester' => $request->jenis_magang === 'mahasiswa' ? $request->semester : null,
            'periode' => $periodeString,
            'status' => 'Menunggu',
            'cv_path' => $cvPath,
            'cv_name' => $cvFile->getClientOriginalName(),
            'transkrip_path' => $transkripPath,
            'transkrip_name' => $transkripFile->getClientOriginalName(),
            'surat_permohonan_path' => $suratPermohonanPath,
            'surat_permohonan_name' => $suratPermohonanFile->getClientOriginalName(),
            'surat_path' => $suratPengantarPath,
            'surat_name' => $suratPengantarName,
            'dokumen_pendukung_path' => !empty($dokumenPendukungPaths) ? $dokumenPendukungPaths : null,
            'dokumen_pendukung_name' => !empty($dokumenPendukungNames) ? $dokumenPendukungNames : null,
        ]);

        return redirect()->route('magang.status')->with('success', 'Pendaftaran berhasil! Berkas Anda sedang kami verifikasi.');
    }

    public function cekStatusForm()
    {
        return view('magang.cek_status_form');
    }

    public function cekStatusResult(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $pendaftar = Pendaftar::where('email', $request->email)->first();

        if (!$pendaftar) {
            return back()->with('error', 'Email tidak ditemukan. Pastikan Anda sudah mendaftar.');
        }

        return view('magang.status', compact('pendaftar'));
    }

    public function adminDashboard(Request $request)
    {
        $query = Pendaftar::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('asal_kampus', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('filter_jenis')) {
            $query->where('jenis_magang', $request->filter_jenis);
        }

        $pendaftars = $query->paginate(10)->withQueryString();

        $total_pelamar = Pendaftar::count();
        $total_menunggu = Pendaftar::where('status', 'Menunggu')->count();
        $total_diterima = Pendaftar::where('status', 'Diterima')->count();
        $total_ditolak = Pendaftar::where('status', 'Ditolak')->count();
        $total_fg = Pendaftar::where('jenis_magang', 'fresh_graduate')->count();
        $total_mhs = Pendaftar::where('jenis_magang', 'mahasiswa')->count();
        $total_wawancara = Pendaftar::where('status', 'Wawancara')->count();

        return view('magang.admin.dashboard', compact(
            'pendaftars',
            'total_pelamar',
            'total_menunggu',
            'total_diterima',
            'total_ditolak',
            'total_fg',
            'total_mhs',
            'total_wawancara'
        ));
    }

    public function exportExcel(Request $request)
    {
        $query = Pendaftar::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('asal_kampus', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('filter_jenis')) {
            $query->where('jenis_magang', $request->filter_jenis);
        }

        $pendaftars = $query->get();
        $filename = 'Data-Pelamar-Magang-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($pendaftars): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Nama Lengkap', 'Jenis Magang', 'Tipe Mahasiswa', 'Email', 'No HP', 'Asal Kampus', 'Jurusan', 'IPK', 'Semester', 'Periode', 'Status']);

            foreach ($pendaftars as $index => $row) {
                $tipeMhs = '-';
                if ($row->tipe_mahasiswa === 'pkl') {
                    $tipeMhs = 'PKL';
                } elseif ($row->tipe_mahasiswa === 'riset') {
                    $tipeMhs = 'Riset/Penelitian';
                }

                fputcsv($file, [
                    $index + 1,
                    $row->nama,
                    $row->jenis_magang === 'fresh_graduate' ? 'Fresh Graduate' : 'Mahasiswa',
                    $tipeMhs,
                    $row->email,
                    $row->no_hp ? "'" . $row->no_hp : '-',
                    $row->asal_kampus,
                    $row->jurusan,
                    $row->ipk ?? '-',
                    $row->semester ?? '-',
                    $row->periode,
                    $row->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function adminDetail($id)
    {
        $pendaftar = Pendaftar::findOrFail($id);
        return view('magang.admin.detail', compact('pendaftar'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Menunggu,Wawancara,Diterima,Ditolak',
        ]);

        $pendaftar = Pendaftar::findOrFail($id);

        if ($request->status === 'Wawancara') {
            $request->validate([
                'tgl_wawancara' => 'required|date',
                'lokasi' => 'required|string',
                'pesan' => 'nullable|string',
            ]);

            $pendaftar->wawancara_waktu = $request->tgl_wawancara;
            $pendaftar->wawancara_lokasi = $request->lokasi;
            $pendaftar->pesan = $request->pesan;
        } else {
            if ($request->filled('pesan')) {
                $pendaftar->pesan = $request->pesan;
            }
        }

        $pendaftar->status = $request->status;
        $pendaftar->save();

        return back()->with('success', 'Status berhasil diperbarui menjadi ' . $request->status . '.');
    }
}
