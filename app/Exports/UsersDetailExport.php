<?php

namespace App\Exports;

use App\Models\AbsenceRequest;
use App\Models\Attendance;
use App\Models\AttendanceDraftDocument;
use App\Models\FinalReport;
use App\Models\Leave;
use App\Models\MonthlyReport;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersDetailExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithCustomValueBinder
{
    // Kolom yang dipaksa jadi teks: D = Nomor HP, Q = HP Kontak Darurat, T = Nomor Rekening
    private const TEXT_COLUMNS = ['D', 'Q', 'T'];

    private $users;
    private $counts = [];

    public function collection()
    {
        // Satu query per tabel (dikelompokkan per user_id), bukan satu query per peserta
        $count = function ($model) {
            return $model::selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id');
        };

        $this->counts = [
            'attendance'      => $count(Attendance::class),
            'leave'           => $count(Leave::class),
            'absence_request' => $count(AbsenceRequest::class),
            'monthly_report'  => $count(MonthlyReport::class),
            'final_report'    => $count(FinalReport::class),
            'draft_document'  => $count(AttendanceDraftDocument::class),
        ];

        $this->users = User::all()->keyBy('email');

        return User::with(['division', 'mentor'])->orderByName()->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Nama', 'Email', 'Nomor HP', 'Tanggal Lahir', 'Alamat',
            'Asal Kampus', 'Jurusan', 'Divisi', 'Mentor',
            'Mulai Magang', 'Selesai Magang', 'Durasi (bulan)', 'Status', 'Terdaftar Sejak',
            'Kontak Darurat', 'HP Kontak Darurat', 'Hubungan',
            'Nama Bank', 'Nomor Rekening', 'Akun Login',
            'Total Presensi Masuk', 'Total Presensi Pulang',
            'Total Ketidakhadiran', 'Laporan Bulanan', 'Laporan Akhir', 'Draft Presensi',
        ];
    }

    public function map($e): array
    {
        $start = $e->internship_start_date ? Carbon::parse($e->internship_start_date)->startOfDay() : null;
        $end   = $e->internship_end_date ? Carbon::parse($e->internship_end_date)->endOfDay() : null;

        // Sama dengan logika di halaman daftar peserta
        $months = ($start && $end)
            ? max(($end->year - $start->year) * 12 + ($end->month - $start->month) + 1, 1)
            : null;
        $isActive = $start && $end && Carbon::now()->between($start, $end);

        $user = $e->email ? ($this->users[$e->email] ?? null) : null;
        $n = function ($key) use ($e) {
            return $this->counts[$key][$e->id] ?? 0;
        };

        return [
            $e->id,
            $e->name,
            $e->email,
            $e->phone_number,
            $e->birth_date ? Carbon::parse($e->birth_date)->format('d-m-Y') : '',
            $e->address,
            $e->institution,
            $e->major,
            optional($e->division)->name,
            optional($e->mentor)->name,
            $start ? $start->format('d-m-Y') : '',
            $end ? $end->format('d-m-Y') : '',
            $months,
            $isActive ? 'Active' : 'Inactive',
            optional($e->created_at)->format('d-m-Y H:i'),
            $e->emergency_contact_name,
            $e->emergency_contact_phone,
            $e->emergency_contact_relation,
            $e->bank_name,
            $e->bank_account_number,
            $user ? $user->name . ' (' . $user->email . ')' : 'Belum terhubung',
            $n('attendance'),
            $n('leave'),
            $n('absence_request'),
            $n('monthly_report'),
            $n('final_report'),
            $n('draft_document'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $last = $sheet->getHighestColumn();
        $row  = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$last}1")->getFill()
            ->setFillType('solid')->getStartColor()->setARGB('FFE9EAF7');
        $sheet->getStyle("A1:{$last}{$row}")->getBorders()->getAllBorders()->setBorderStyle('thin');
        $sheet->freezePane('C2'); // header + kolom ID/Nama tetap terlihat saat scroll

        return [];
    }

    // Paksa kolom tertentu jadi teks agar nomor rekening/HP tidak berubah jadi notasi ilmiah
    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), self::TEXT_COLUMNS, true) && $value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
