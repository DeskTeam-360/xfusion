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
    protected $fillable=['user_id', 'title', 'logo_url', 'qrcode_url','company_url'];

    public function companyEmployees()
    {
        return $this->hasMany(CompanyEmployee::class,'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
