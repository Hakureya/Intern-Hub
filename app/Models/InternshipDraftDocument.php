<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternshipDraftDocument extends Model
{
    protected $fillable = [
        'uploaded_by',
        'document_type',
        'library_category',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    /**
     * Pecah deskripsi menjadi paragraf pengantar dan poin-poin.
     * Baris yang diawali "- ", "* " atau "• " menjadi poin; baris lain menjadi pengantar.
     * ini bagian untuk mengganti deskripsi pada laporan bulanan dan laporan akhir.
     */
    public function getDescriptionPartsAttribute(): array
    {
        $intro = [];
        $items = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $this->description) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^[-*•]\s+(.+)$/u', $line, $m)) {
                $items[] = trim($m[1]);
            } else {
                $intro[] = $line;
            }
        }

        return ['intro' => implode(' ', $intro), 'items' => $items];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
