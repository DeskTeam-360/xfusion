<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseScoringGroupDetail extends Model
{
    use HasFactory;

    protected $table = 'wp_course_scoring_group_details';

    protected $fillable = ['course_scoring_group_id', 'form_id', 'field_id', 'weight'];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function courseScoringGroup()
    {
        return $this->belongsTo(CourseScoringGroup::class, 'course_scoring_group_id');
    }
}
