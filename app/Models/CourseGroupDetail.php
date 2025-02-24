<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $course_group_id
 * @property $course_list_id
 */
class CourseGroupDetail extends Model
{
    use HasFactory;

    protected $fillable = ['course_group_id', 'course_list_id','orders'];

    public function courseGroup()
    {
        return $this->belongsTo(CourseGroup::class, 'course_group_id');
    }

    public function courseList()
    {
        return $this->belongsTo(CourseList::class, 'course_list_id');
    }

}

