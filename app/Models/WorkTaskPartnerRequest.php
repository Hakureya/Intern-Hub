<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTaskPartnerRequest extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const DECLINED = 'declined';

    public const LABELS = [
        self::PENDING => 'Menunggu',
        self::ACCEPTED => 'Diterima',
        self::DECLINED => 'Ditolak',
    ];

    protected $fillable = ['work_task_id', 'from_user_id', 'to_user_id', 'status', 'responded_at'];

    protected $casts = ['responded_at' => 'datetime'];

    public function task()
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function from()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
