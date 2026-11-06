<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model

{

    protected $table = 'attendances';
    
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
