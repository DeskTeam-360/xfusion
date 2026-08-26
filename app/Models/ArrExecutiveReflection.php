<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Step 4 — Executive Strategic Reflection™. One row per ARR (unique on
 * arr_id); the 8 free-text prompts plus shared conversation notes captured
 * during the executive reflection conversation.
 */
class ArrExecutiveReflection extends Model
{
    protected $table = 'wp_fusion_arr_executive_reflections';

    protected $fillable = [
        'arr_id',
        'organizational_learning',
        'readiness_progression',
        'strategic_assumptions',
        'organizational_barriers',
        'organizational_strengths',
        'leadership_effectiveness',
        'resource_allocation',
        'future_opportunities',
        'conversation_notes',
        'author_user_id',
    ];

    public function arr()
    {
        return $this->belongsTo(Arr::class, 'arr_id');
    }
}
