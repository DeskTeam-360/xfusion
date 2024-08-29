<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $user_id
 * @property integer $company_id
 * @property string $created_at
 * @property string $updated_at
 */

class CompanyEmployee extends Model
{
    protected $table='company_employees';
    use HasFactory;
    protected $fillable=['user_id', 'company_id'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
}
