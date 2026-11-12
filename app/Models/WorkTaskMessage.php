<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTaskMessage extends Model
{
    public const TEXT = 'text';
    public const EDIT = 'edit';

    protected $fillable = ['work_task_id', 'user_id', 'kind', 'body', 'changes', 'edited_at'];

    protected $casts = [
        'changes' => 'array',
        'edited_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function files()
    {
        return $this->hasMany(WorkTaskMessageFile::class, 'message_id')->orderBy('id');
    }
}
