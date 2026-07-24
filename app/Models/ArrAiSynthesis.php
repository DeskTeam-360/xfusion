<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrAiSynthesis extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_arr_ai_syntheses';

    protected $fillable = [
        'arr_id',
        'synthesis',
        'insight_model',
        'prompt_version_id',
        'prompt_version_label',
        'tokens_used',
        'cost_usd',
    ];

    protected $casts = [
        'synthesis' => 'array',
        'tokens_used' => 'integer',
        'cost_usd' => 'decimal:4',
    ];

    public function arr()
    {
        return $this->belongsTo(Arr::class, 'arr_id');
    }
}
