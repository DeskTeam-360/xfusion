<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QbrAiAssessment extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_qbr_ai_assessments';

    protected $fillable = [
        'qbr_id',
        'assessment',
        'leadership_context',
        'agreement_rating',
        'insight_model',
        'tokens_used',
        'cost_usd',
    ];

    protected $casts = [
        'assessment' => 'array',
        'cost_usd' => 'decimal:4',
    ];

    public function qbr()
    {
        return $this->belongsTo(Qbr::class, 'qbr_id');
    }
}
