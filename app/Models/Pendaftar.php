<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftar extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis_magang',
        'tipe_mahasiswa',
        'nama',
        'email',
        'no_hp',
        'alamat',
        'asal_kampus',
        'jurusan',
        'ipk',
        'semester',
        'periode',
        'status',
        'pesan',
        'wawancara_waktu',
        'wawancara_lokasi',
        'cv_path',
        'cv_name',
        'transkrip_path',
        'transkrip_name',
        'surat_path',
        'surat_name',
        'surat_permohonan_path',
        'surat_permohonan_name',
        'dokumen_pendukung_path',
        'dokumen_pendukung_name',
    ];

    protected $casts = [
        'wawancara_waktu' => 'datetime',
        'dokumen_pendukung_path' => 'array',
        'dokumen_pendukung_name' => 'array',
    ];

    public function setNoHpAttribute($value): void
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', (string) $value);
        if (substr($cleanNumber, 0, 2) === '08') {
            $this->attributes['no_hp'] = '62' . substr($cleanNumber, 1);
            return;
        }

        $this->attributes['no_hp'] = $cleanNumber;
    }
}
