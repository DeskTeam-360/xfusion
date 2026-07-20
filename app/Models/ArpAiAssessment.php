<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpAiAssessment extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_arp_ai_assessments';

    protected $fillable = [
        'arp_id',
        'assessment',
        'leadership_context',
        'insight_model',
        'tokens_used',
        'cost_usd',
    ];

    protected $casts = [
        'assessment' => 'array',
        'cost_usd' => 'decimal:4',
    ];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }
}
