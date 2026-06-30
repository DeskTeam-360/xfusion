<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyGroup extends Model
{
    use HasFactory;

    public const STATUS_MEMBER = 'member';

    public const STATUS_LEADER = 'leader';

    protected $table = 'wp_company_groups';

    protected $fillable = ['company_id', 'title', 'description'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function details()
    {
        return $this->hasMany(CompanyGroupDetail::class, 'company_group_id', 'id');
    }

    protected static function booted(): void
    {
        static::deleting(static function (CompanyGroup $group): void {
            $group->details()->delete();
        });
    }
}
