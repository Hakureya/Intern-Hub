<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyReport extends Model
{
    use HasFactory;

    protected $table = 'monthly_reports';

    protected $fillable = [
        'user_id',
        'uploaded_by',
        'report_month',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
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
