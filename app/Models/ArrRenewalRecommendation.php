<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrRenewalRecommendation extends Model
{
    use HasFactory;

    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CARRIED_TO_ARP = 'carried_to_arp';

    protected $table = 'wp_fusion_arr_renewal_recommendations';

    protected $fillable = [
        'arr_id',
        'title',
        'description',
        'priority',
        'executive_owner_user_id',
        'executive_owner_name',
        'cor_capability',
        'behavioral_driver',
        'expected_organizational_impact',
        'recommended_timeline',
        'status',
        'priority_rank',
    ];

    public function arr()
    {
        return $this->belongsTo(Arr::class, 'arr_id');
    }

    public function executiveOwner()
    {
        return $this->belongsTo(User::class, 'executive_owner_user_id');
    }
}
