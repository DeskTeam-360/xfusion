<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $title
 * @property $sub_title
 * @property $type
 */
class CourseGroup extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'sub_title', 'type'];

    public function courseGroupDetails()
    {
        return $this->hasMany(CourseGroupDetail::class,'course_group_id');
    }

}
