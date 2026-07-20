<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FusionEvidenceLog extends Model
{
    use HasFactory;

    public const SOURCE_ARP = 'arp';

    public const EVENT_ARP_PUBLISHED = 'arp_published';

    public const EVENT_ARP_ARCHIVED = 'arp_archived';

    public const EVENT_AI_READINESS_REVIEW = 'ai_readiness_review_generated';

    protected $table = 'wp_fusion_evidence_log';

    protected $fillable = [
        'source_type',
        'source_id',
        'event_type',
        'user_id',
        'behavioral_driver',
        'cor_capability',
        'evidence_date',
        'metadata',
    ];

    protected $casts = [
        'evidence_date' => 'date',
        'metadata' => 'array',
    ];
}
