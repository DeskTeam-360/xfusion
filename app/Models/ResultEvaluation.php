<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * COR Unified Insights results (wp_xfusion_result_evaluations). Written by
 * the WordPress plugin's evaluate-unified flow; read-only from Laravel.
 * `evaluation.performance` holds the 5 Behavioral Driver strength/opportunity
 * pairs, `evaluation.cor_organization_capabilities` the narrative insight.
 * `score` is 0-100 (average of capability scores × 20 — see CLAUDE.md).
 */
class ResultEvaluation extends Model
{
    use HasFactory;

    protected $table = 'wp_xfusion_result_evaluations';

    public $timestamps = false;

    protected $casts = [
        'evaluation' => 'array',
        'evaluation_input' => 'array',
        'evaluated_at' => 'datetime',
        'created_at' => 'datetime',
        'cost_usd' => 'decimal:4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }
}
