<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOneAiBrief extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_one_on_one_ai_briefs';

    protected $fillable = ['conversation_id', 'brief', 'insight_model', 'tokens_used', 'cost_usd'];

    protected $casts = [
        'brief' => 'array',
        'cost_usd' => 'decimal:4',
    ];

    public function conversation()
    {
        return $this->belongsTo(OneOnOneConversation::class, 'conversation_id');
    }
}
