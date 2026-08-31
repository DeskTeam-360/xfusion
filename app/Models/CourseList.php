<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseList extends Model
{
    use HasFactory;

    protected $table = 'wp_course_lists';

    protected $fillable = ['url', 'course_title', 'page_title', 'wp_gf_form_id', 'lms_topic_id', 'keap_tag', 'keap_tag_next', 'delay', 'url_next', 'repeat_entry', 'legacy', 'icon'];

    protected $casts = [
        'lms_topic_id' => 'integer',
    ];

    public function courseGroupDetails()
    {
        return $this->hasMany(CourseGroupDetail::class, 'course_list_id');
    }

    /** Which of the 5 COR Organizational Capabilities this tool is tagged with, for the Tool Library catalog display. */
    public function corCapabilityTags()
    {
        return $this->belongsToMany(
            CourseScoringGroup::class,
            'wp_fusion_tool_scoring_group_tags',
            'course_list_id',
            'course_scoring_group_id'
        )->withTimestamps();
    }
}
