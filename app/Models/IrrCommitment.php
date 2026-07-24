<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Step 5 — Annual Development Commitments™. Stored in the legacy
 * wp_fusion_360_commitments table (extended by wp_fusion_irr_wizard.sql).
 * Max 5 per review, enforced in IrrController::saveCommitments().
 */
class IrrCommitment extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    protected $table = 'wp_fusion_360_commitments';

    protected $fillable = [
        'review_id',
        'title',
        'description',
        'owner_user_id',
        'owner_name',
        'priority',
        'success_indicator',
        'behavioral_driver',
        'org_priority_type',
        'org_priority_label',
        'status',
        'due_date',
        'priority_rank',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function review()
    {
        return $this->belongsTo(IrrReview::class, 'review_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
