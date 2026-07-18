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

    protected $fillable = ['company_id', 'company_group_id', 'year', 'title', 'mission', 'vision', 'status', 'version', 'published_at', 'created_by'];

    protected $casts = [
        'published_at' => 'datetime',
        'version' => 'decimal:1',
    ];

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
