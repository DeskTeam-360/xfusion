<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//$table->string('header');
//$table->string('title');
//$table->string('sub-title');
//$table->integer('week');
//$table->string('url');
//$table->string('parent_url');

/**
 * @property string $header
 * @property string $title
 * @property string $sub_title
 * @property string $week
 * @property string $url
 * @property string $parent_url
 */
class CourseScheduleGenerateTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['header', 'title', 'week', 'url', 'sub_title', 'parent_url'];
//'link', '', 'status', 'title','schedule_access','schedule_deadline'

}
