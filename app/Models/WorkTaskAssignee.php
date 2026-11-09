<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTaskAssignee extends Model
{
    public const BELUM = 'belum';
    public const MENUNGGU = 'menunggu';
    public const SUDAH = 'sudah';

    public const LABELS = [
        self::BELUM => 'Belum dikerjakan',
        self::MENUNGGU => 'Menunggu persetujuan',
        self::SUDAH => 'Sudah selesai',
    ];

    protected $fillable = [
        'work_task_id',
        'user_id',
        'status',
        'proof_note',
        'proof_path',
        'proof_name',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(WorkTaskAttachment::class, 'work_task_assignee_id')->orderBy('id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
