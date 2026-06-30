<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOneAiSynthesis extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_one_on_one_ai_syntheses';

    protected $fillable = ['conversation_id', 'synthesis', 'insight_model', 'tokens_used', 'cost_usd'];

    protected $casts = [
        'synthesis' => 'array',
        'cost_usd' => 'decimal:4',
    ];

    public function conversation()
    {
        return $this->belongsTo(OneOnOneConversation::class, 'conversation_id');
    }
}
