<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Individual Readiness Review™ (IRR) — stored in legacy wp_fusion_360_reviews.
 */
class IrrReview extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_READY = 'ready_to_publish';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'wp_fusion_360_reviews';

    protected $fillable = [
        'employee_user_id',
        'manager_user_id',
        'company_id',
        'company_group_id',
        'year',
        'status',
        'created_by',
        'started_at',
        'published_at',
        'step_progress',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'published_at' => 'datetime',
        'step_progress' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function companyGroup()
    {
        return $this->belongsTo(CompanyGroup::class, 'company_group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evidenceSnapshots()
    {
        return $this->hasMany(IrrEvidenceSnapshot::class, 'review_id')->orderByDesc('id');
    }

    public function commitments()
    {
        return $this->hasMany(IrrCommitment::class, 'review_id')->orderBy('priority_rank');
    }
}
