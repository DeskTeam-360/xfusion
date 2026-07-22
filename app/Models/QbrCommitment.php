<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QbrCommitment extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUS_CARRIED_FORWARD = 'carried_forward';

    protected $table = 'wp_fusion_qbr_commitments';

    protected $fillable = [
        'qbr_id',
        'title',
        'description',
        'owner_user_id',
        'owner_name',
        'priority',
        'related_arp_objective',
        'success_measure',
        'due_date',
        'status',
        'carried_forward_from_id',
        'priority_rank',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function qbr()
    {
        return $this->belongsTo(Qbr::class, 'qbr_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function carriedForwardFrom()
    {
        return $this->belongsTo(self::class, 'carried_forward_from_id');
    }
}
