<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpKpi extends Model
{
    use HasFactory;

    public const TYPE_LEADING = 'leading';

    public const TYPE_TRAILING = 'trailing';

    protected $table = 'wp_fusion_arp_kpis';

    protected $fillable = [
        'arp_id',
        'name',
        'type',
        'description',
        'why_it_matters',
        'current_baseline',
        'target_value',
        'target_date',
        'measurement_frequency',
        'data_source',
        'owner_user_id',
        'readiness_priority_ids',
        'notes',
        'priority_rank',
    ];

    protected $casts = [
        'readiness_priority_ids' => 'array',
        'target_date' => 'date',
    ];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
