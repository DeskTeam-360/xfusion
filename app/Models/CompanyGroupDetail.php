<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyGroupDetail extends Model
{
    use HasFactory;

    protected $table = 'wp_company_group_details';

    protected $fillable = ['company_group_id', 'user_id', 'status'];

    public function companyGroup()
    {
        return $this->belongsTo(CompanyGroup::class, 'company_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function validStatuses(): array
    {
        return [CompanyGroup::STATUS_MEMBER, CompanyGroup::STATUS_LEADER];
    }

    public function isLeader(): bool
    {
        return $this->status === CompanyGroup::STATUS_LEADER;
    }
}
