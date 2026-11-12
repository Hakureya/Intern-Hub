<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTaskMessageFile extends Model
{
    protected $fillable = ['message_id', 'name', 'path', 'mime', 'size'];

    public function message()
    {
        return $this->belongsTo(WorkTaskMessage::class, 'message_id');
    }

    /** image | video | file (menentukan bentuk preview di ruang chat). */
    public function kind(): string
    {
        $mime = (string) $this->mime;

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return 'image';
        }
        if (in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
            return 'video';
        }

        return 'file';
    }
}
