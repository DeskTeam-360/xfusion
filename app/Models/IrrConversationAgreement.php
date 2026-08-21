<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * IRR Step 4 — Development Conversation™ shared notes + dual digital
 * signatures. One row per review (r360ca_review_uq unique key).
 */
class IrrConversationAgreement extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_360_conversation_agreements';

    protected $fillable = [
        'review_id',
        'conversation_notes',
        'conversation_date',
        'employee_signed_at',
        'employee_signature_name',
        'leader_signed_at',
        'leader_signature_name',
    ];

    protected $casts = [
        'conversation_date' => 'date',
        'employee_signed_at' => 'datetime',
        'leader_signed_at' => 'datetime',
    ];

    public function review()
    {
        return $this->belongsTo(IrrReview::class, 'review_id');
    }
}
