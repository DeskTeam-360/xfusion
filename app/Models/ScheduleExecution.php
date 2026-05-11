<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bekas fitur jadwal (tabel schedule_executions dihapus).
 * Model tetap ada agar referensi lama tidak error; query selalu kosong.
 *
 * @deprecated
 */
class ScheduleExecution extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [];

    protected static function booted(): void
    {
        static::addGlobalScope('schedule_feature_removed', function (Builder $builder): void {
            $builder->whereRaw('0 = 1');
        });

        static::creating(static fn () => false);
        static::updating(static fn () => false);
    }
}
