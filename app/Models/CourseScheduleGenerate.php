<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseScheduleGenerate extends Model
{
    use HasFactory;
    protected $fillable= ['course_list_id','course_list_parent_id','week'];

    public function courseList(): BelongsTo
    {
        return $this->belongsTo(CourseList::class,'course_list_id');
    }
    public function courseListParent(): BelongsTo
    {
        return $this->belongsTo(CourseList::class,'course_list_parent_id');
    }
}
