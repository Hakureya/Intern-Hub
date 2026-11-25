<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCategory extends Model
{
    protected $fillable = ['division_id', 'name'];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /** Nama kategori untuk dropdown tugas harian. Divisi tanpa daftar memakai kategori standar. */
    public static function namesFor($divisionId): array
    {
        $names = $divisionId
            ? static::where('division_id', $divisionId)->orderBy('name')->pluck('name')->all()
            : [];

        return $names ?: WorkTask::DEFAULT_CATEGORIES;
    }

    /** Isi kategori standar bila divisi belum punya daftar. */
    public static function seedDefaults($divisionId): void
    {
        if (! $divisionId || static::where('division_id', $divisionId)->exists()) {
            return;
        }
        foreach (WorkTask::DEFAULT_CATEGORIES as $name) {
            static::create(['division_id' => $divisionId, 'name' => $name]);
        }
    }
}
