<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arp extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'wp_fusion_arps';

    protected $fillable = [
        'company_id',
        'company_group_id',
        'year',
        'title',
        'mission',
        'vision',
        'core_values',
        'organizational_description',
        'business_environment',
        'executive_narrative',
        'step_progress',
        'status',
        'version',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'version' => 'decimal:1',
        'step_progress' => 'array',
    ];

    public function futureState()
    {
        return $this->hasOne(ArpFutureState::class, 'arp_id');
    }

    public function learnings()
    {
        return $this->hasMany(ArpLearning::class, 'arp_id');
    }

    public function readinessPriorities()
    {
        return $this->hasMany(ArpReadinessPriority::class, 'arp_id')->orderBy('priority_rank');
    }

    public function strategicPriorities()
    {
        return $this->hasMany(ArpStrategicPriority::class, 'arp_id')->orderBy('priority_rank');
    }

    public function aiAssessments()
    {
        return $this->hasMany(ArpAiAssessment::class, 'arp_id')->orderByDesc('id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** The real scoping unit — one ARP per group per year. */
    public function companyGroup()
    {
        return $this->belongsTo(CompanyGroup::class, 'company_group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions()
    {
        return $this->hasMany(ArpVersion::class, 'arp_id')->orderByDesc('id');
    }
}
