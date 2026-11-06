<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDraftDocument extends Model
{
    protected $fillable = [
        'user_id',
        'uploaded_by',
        'report_month',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
