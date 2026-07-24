<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Annual Readiness Review™ (ARR) — organization-wide, one per
 * (company_id, year). Sits above ARP/QBR/1-on-1/IRR in the FUSION cycle,
 * synthesizing a full year of evidence into strategic renewal
 * recommendations that seed the next ARP.
 */
class Arr extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_READY_TO_PUBLISH = 'ready_to_publish';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'wp_fusion_arrs';

    protected $fillable = [
        'company_id',
        'executive_owner_user_id',
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

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function executiveOwner()
    {
        return $this->belongsTo(User::class, 'executive_owner_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evidenceSnapshots()
    {
        return $this->hasMany(ArrEvidenceSnapshot::class, 'arr_id')->orderByDesc('id');
    }

    public function aiAssessments()
    {
        return $this->hasMany(ArrAiAssessment::class, 'arr_id')->orderByDesc('id');
    }

    public function renewalRecommendations()
    {
        return $this->hasMany(ArrRenewalRecommendation::class, 'arr_id')->orderBy('priority_rank');
    }

    public function aiSyntheses()
    {
        return $this->hasMany(ArrAiSynthesis::class, 'arr_id')->orderByDesc('id');
    }
}
