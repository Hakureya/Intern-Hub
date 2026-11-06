<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuang kolom attendances.status_type.
 *
 * Kolom ini hanya diisi oleh form input manual /check yang sudah dihapus. Form itu
 * menyimpan sakit/izin/tanpa keterangan/cuti/perjalanan dinas sebagai baris absen
 * (jam 00:00:00 jika belum ada absen hari itu) dengan status_type terisi.
 *
 * Agar laporan lama tidak berubah menjadi "Hadir", data tersebut lebih dulu
 * dipindahkan menjadi pengajuan di absence_requests:
 *   sakit            -> approved, tipe sakit
 *   izin             -> approved, tipe izin
 *   cuti             -> approved, tipe izin  (keterangan memuat kata "cuti")
 *   perjalanan_dinas -> approved, tipe izin  (keterangan memuat "perjalanan dinas")
 *   tanpa_keterangan -> rejected             (Sheet Report menampilkannya sebagai TK)
 * Baris absen palsu jam 00:00:00 dihapus. Tanggal yang sudah punya pengajuan
 * disetujui/ditolak tidak dibuatkan pengajuan baru.
 *
 * down() hanya membuat kolom kembali (kosong); konversi data tidak dibatalkan.
 */
class DropStatusTypeFromAttendancesTable extends Migration
{
    private const MAP = [
        'sakit'            => ['sakit', 'approved', 'Sakit'],
        'izin'             => ['izin', 'approved', 'Izin'],
        'cuti'             => ['izin', 'approved', 'Cuti'],
        'perjalanan_dinas' => ['izin', 'approved', 'Perjalanan dinas'],
        'tanpa_keterangan' => ['izin', 'rejected', 'Tanpa keterangan'],
    ];

    public function up()
    {
        DB::transaction(function () {
            $rows = DB::table('attendances')
                ->whereNotNull('status_type')
                ->where('status_type', '!=', '')
                ->get(['id', 'emp_id', 'attendance_date', 'attendance_time', 'status_type']);

            foreach ($rows as $row) {
                $key = strtolower(trim((string) $row->status_type));
                if (!isset(self::MAP[$key])) {
                    continue;
                }

                [$type, $status, $label] = self::MAP[$key];
                $date = substr((string) $row->attendance_date, 0, 10);

                $alreadyCovered = DB::table('absence_requests')
                    ->where('emp_id', $row->emp_id)
                    ->whereIn('status', ['approved', 'rejected'])
                    ->whereDate('absence_date', '<=', $date)
                    ->whereRaw('DATE(COALESCE(absence_end_date, absence_date)) >= ?', [$date])
                    ->exists();

                if (!$alreadyCovered) {
                    DB::table('absence_requests')->insert([
                        'emp_id'           => $row->emp_id,
                        'absence_date'     => $date,
                        'absence_end_date' => $date,
                        'absence_type'     => $type,
                        'reason'           => $label . ' (dimigrasikan dari input manual admin)',
                        'status'           => $status,
                        'admin_note'       => 'Dimigrasikan otomatis dari kolom attendances.status_type',
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }

                // Baris absen palsu hasil input manual (jam 00:00:00) tidak boleh dihitung hadir.
                if (substr((string) $row->attendance_time, 0, 8) === '00:00:00') {
                    DB::table('attendances')->where('id', $row->id)->delete();
                }
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('status_type');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status_type')->nullable()->after('status');
        });
    }
}
