<?php

namespace App\Models;

/** Lampiran referensi tugas dari mentor (file atau tautan). isLink() dan icon() diwarisi dari WorkTaskAttachment. */
class WorkTaskReference extends WorkTaskAttachment
{
    protected $table = 'work_task_references';

    protected $fillable = ['work_task_id', 'type', 'name', 'path', 'url'];

    public function task()
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }
}
