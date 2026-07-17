<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpStrategicPriority extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUS_AT_RISK = 'at_risk';

    protected $table = 'wp_fusion_arp_strategic_priorities';

    protected $fillable = [
        'arp_id',
        'readiness_priority_id',
        'title',
        'description',
        'owner_user_id',
        'target_date',
        'success_measures',
        'org_kpi',
        'readiness_indicator',
        'related_groups',
        'kpi',
        'status',
        'priority_rank',
    ];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }

    public function readinessPriority()
    {
        return $this->belongsTo(ArpReadinessPriority::class, 'readiness_priority_id');
    }
}
