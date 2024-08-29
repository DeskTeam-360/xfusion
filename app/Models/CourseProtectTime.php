<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $url
 * @property string $created_at
 * @property string $updated_at
 */

class CourseProtectTime extends Model
{
    use HasFactory;
    protected $fillable=['url'];

    public function companyEmployees()
    {
        return $this->hasMany(CompanyEmployee::class,'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
