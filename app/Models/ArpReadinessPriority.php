<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpReadinessPriority extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_arp_readiness_priorities';

    protected $fillable = [
        'arp_id',
        'name',
        'cor_capability',
        'primary_driver',
        'secondary_driver',
        'priority_level',
        'description',
        'business_rationale',
        'executive_owner_user_id',
        'expected_impact',
        'priority_rank',
    ];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }
}
