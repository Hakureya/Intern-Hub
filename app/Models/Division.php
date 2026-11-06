<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = ['name'];

    public function mentors()
    {
        return $this->hasMany(Mentor::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'division_id')->peserta();
    }
}
