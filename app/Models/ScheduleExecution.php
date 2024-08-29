<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $link
 * @property int $company_id
 * @property int $user_id
 * @property int $status
 * @property string $title
 * @property string $schedule_access
 * @property string $schedule_deadline
 * @property string $created_at
 * @property string $updated_at
 */

class ScheduleExecution extends Model
{
    use HasFactory;
    protected $fillable=['link', 'company_id', 'user_id', 'season_id', 'status', 'title','schedule_access','schedule_deadline'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class,'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

}
