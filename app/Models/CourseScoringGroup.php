<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseScoringGroup extends Model
{
    use HasFactory;

    protected $table = 'wp_course_scoring_groups';

    protected $fillable = ['title', 'description'];

    public function details()
    {
        return $this->hasMany(CourseScoringGroupDetail::class, 'course_scoring_group_id', 'id');
    }

    protected static function booted(): void
    {
        static::deleting(static function (CourseScoringGroup $group): void {
            $group->details()->delete();
        });
    }
}
