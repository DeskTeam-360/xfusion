<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $logo_url
 * @property string $qrcode_url
 * @property string $company_url
 * @property string $created_at
 * @property string $updated_at
 */

class Company extends Model
{
    use HasFactory;

    // Pinned explicitly: Eloquent's newRelatedInstance() inherits the
    // caller's connection for any related model that doesn't set its own,
    // so being eager-loaded from a 'wordpress'-connection model (e.g.
    // CompanyEmployee::with('company')) would otherwise resolve this model
    // on the 'wordpress' connection too — double-prefixing the already
    // wp_-prefixed table name into wp_wp_companies.
    protected $connection = 'mysql';

    protected $table = 'wp_companies';

    protected $fillable = [
        'user_id', 'title', 'logo_url', 'qrcode_url', 'company_url',
        'role', 'team', 'organizational_goals', 'readiness_priorities',
    ];

    public function companyEmployees()
    {
        return $this->hasMany(CompanyEmployee::class,'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function companyGroups()
    {
        return $this->hasMany(CompanyGroup::class, 'company_id');
    }
}
