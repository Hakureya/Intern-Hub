<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public const FIXED_TIME_IN = '08:00:00';
    public const FIXED_TIME_OUT = '16:30:00';

    /** Jam masuk format H:i (mis. 08:00), turunan dari FIXED_TIME_IN. */
    public static function timeInShort(): string
    {
        return substr(self::FIXED_TIME_IN, 0, 5);
    }

    /** Jam pulang format H:i (mis. 16:30), turunan dari FIXED_TIME_OUT. */
    public static function timeOutShort(): string
    {
        return substr(self::FIXED_TIME_OUT, 0, 5);
    }

    /** Nama jadwal bawaan, mis. "Default 08:00-16:30". */
    public static function defaultSlug(): string
    {
        return 'Default ' . self::timeInShort() . '-' . self::timeOutShort();
    }

    protected static function booted()
    {
        static::saving(function (self $schedule) {
            $schedule->time_in = self::FIXED_TIME_IN;
            $schedule->time_out = self::FIXED_TIME_OUT;
        });
    }

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function getTimeInAttribute($value)
    {
        return self::FIXED_TIME_IN;
    }

    public function getTimeOutAttribute($value)
    {
        return self::FIXED_TIME_OUT;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'schedule_user', 'schedule_id', 'user_id');
    }
}
