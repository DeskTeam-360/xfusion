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

    /** Connected for scoring when weight is null (legacy) or explicitly > 0. */
    public function isConnected(): bool
    {
        if ((int) $this->field_id < 1) {
            return false;
        }

        if ($this->weight === null) {
            return true;
        }

        return (float) $this->weight > 0;
    }

    public function scopeConnected($query)
    {
        return $query
            ->where('field_id', '>', 0)
            ->where(function ($q) {
                $q->whereNull('weight')->orWhere('weight', '>', 0);
            });
    }

    public function courseScoringGroup()
    {
        return $this->belongsTo(CourseScoringGroup::class, 'course_scoring_group_id');
    }
}
