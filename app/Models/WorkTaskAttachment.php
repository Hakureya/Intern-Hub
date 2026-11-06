<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTaskAttachment extends Model
{
    protected $fillable = ['work_task_assignee_id', 'type', 'name', 'path', 'url'];

    public function assignee()
    {
        return $this->belongsTo(WorkTaskAssignee::class, 'work_task_assignee_id');
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    /** Kelas ikon mdi sesuai jenis file. */
    public function icon(): string
    {
        if ($this->isLink()) {
            return 'mdi-link-variant';
        }

        $ext = strtolower(pathinfo((string) ($this->name ?: $this->path), PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'mdi-file-image-outline';
        }
        if ($ext === 'pdf') {
            return 'mdi-file-pdf-box';
        }
        if (in_array($ext, ['doc', 'docx'], true)) {
            return 'mdi-file-word-outline';
        }
        if (in_array($ext, ['xls', 'xlsx'], true)) {
            return 'mdi-file-excel-outline';
        }
        if (in_array($ext, ['ppt', 'pptx'], true)) {
            return 'mdi-file-powerpoint-outline';
        }
        if ($ext === 'zip') {
            return 'mdi-folder-zip-outline';
        }

        return 'mdi-file-outline';
    }
}
