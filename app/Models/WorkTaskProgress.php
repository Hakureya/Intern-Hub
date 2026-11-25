<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTaskProgress extends Model
{
    protected $table = 'work_task_progress';

    protected $fillable = ['work_task_id', 'user_id', 'entry_date', 'quantity', 'note'];

    protected $casts = [
        'entry_date' => 'date',
        'quantity' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
