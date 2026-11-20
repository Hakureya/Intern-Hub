<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = ['name'];

    /** Singkatan untuk id tugas: inisial kata (maks 4 huruf), satu kata -> 3 huruf pertama. */
    public function codeAbbr(): string
    {
        $words = array_filter(preg_split('/[^A-Za-z0-9]+/', (string) $this->name), function ($w) {
            return $w !== '' && ! in_array(mb_strtolower($w), ['dan', 'di', 'dan', 'of', 'the', '&'], true);
        });
        $words = array_values($words);

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 3));
        }

        $out = '';
        foreach (array_slice($words, 0, 4) as $w) {
            $out .= mb_strtoupper(mb_substr($w, 0, 1));
        }

        return $out ?: 'UMM';
    }

    public function mentors()
    {
        return $this->hasMany(Mentor::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'division_id')->peserta();
    }
}
