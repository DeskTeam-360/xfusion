<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseList extends Model
{
    use HasFactory;

    protected $fillable = ['url', 'course_title', 'page_title', 'wp_gf_form_id', 'keap_tag', 'keap_tag_parent', 'delay','url_next'];

    public function courseGroupDetails()
    {
        return $this->hasMany(CourseGroupDetail::class, 'course_list_id');
    }
}
