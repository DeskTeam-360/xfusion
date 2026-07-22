<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QbrAiSynthesis extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_qbr_ai_syntheses';

    protected $fillable = ['qbr_id', 'synthesis', 'insight_model', 'tokens_used', 'cost_usd'];

    protected $casts = [
        'synthesis' => 'array',
        'cost_usd' => 'decimal:4',
    ];

    public function qbr()
    {
        return $this->belongsTo(Qbr::class, 'qbr_id');
    }
}
