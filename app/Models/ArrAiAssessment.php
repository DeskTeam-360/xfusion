<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrAiAssessment extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_arr_ai_assessments';

    protected $fillable = [
        'arr_id',
        'assessment',
        'executive_context',
        'agreement_rating',
        'insight_model',
        'prompt_version_id',
        'prompt_version_label',
        'tokens_used',
        'cost_usd',
    ];

    protected $casts = [
        'assessment' => 'array',
        'tokens_used' => 'integer',
        'cost_usd' => 'decimal:4',
    ];

    public function arr()
    {
        return $this->belongsTo(Arr::class, 'arr_id');
    }
}
