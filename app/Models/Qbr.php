<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qbr extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_HELD = 'held';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'wp_fusion_qbrs';

    protected $fillable = [
        'company_id',
        'company_group_id',
        'facilitator_user_id',
        'quarter',
        'year',
        'status',
        'discussion_notes',
        'held_at',
        'step_progress',
        'created_by',
    ];

    protected $casts = [
        'held_at' => 'datetime',
        'step_progress' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** The real scoping unit — one QBR per group per quarter per year. */
    public function companyGroup()
    {
        return $this->belongsTo(CompanyGroup::class, 'company_group_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evidenceSnapshots()
    {
        return $this->hasMany(QbrEvidenceSnapshot::class, 'qbr_id')->orderByDesc('id');
    }

    public function aiAssessments()
    {
        return $this->hasMany(QbrAiAssessment::class, 'qbr_id')->orderByDesc('id');
    }

    public function commitments()
    {
        return $this->hasMany(QbrCommitment::class, 'qbr_id')->orderBy('id');
    }

    public function kpis()
    {
        return $this->hasMany(QbrKpi::class, 'qbr_id')->orderBy('priority_rank');
    }

    public function decisions()
    {
        return $this->hasMany(QbrDecision::class, 'qbr_id')->orderBy('priority_rank');
    }

    public function aiSyntheses()
    {
        return $this->hasMany(QbrAiSynthesis::class, 'qbr_id')->orderByDesc('id');
    }

    /** The QBR immediately preceding this one for the same group (quarter-1, or Q4 of year-1). */
    public function previousQuarter(): ?self
    {
        [$prevQuarter, $prevYear] = $this->quarter > 1
            ? [$this->quarter - 1, $this->year]
            : [4, $this->year - 1];

        return static::query()
            ->where('company_group_id', $this->company_group_id)
            ->where('quarter', $prevQuarter)
            ->where('year', $prevYear)
            ->first();
    }
}
