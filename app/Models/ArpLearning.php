<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpLearning extends Model
{
    use HasFactory;

    public const TYPE_ASSUMPTION = 'assumption';

    public const TYPE_RISK = 'risk';

    public const TYPE_OPPORTUNITY = 'opportunity';

    public const TYPE_LEARNING_OBJECTIVE = 'learning_objective';

    public const TYPE_LEADERSHIP_QUESTION = 'leadership_question';

    protected $table = 'wp_fusion_arp_learnings';

    protected $fillable = ['arp_id', 'type', 'description'];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }

    /**
     * @return array<string, string>
     */
    public static function uiToTypeMap(): array
    {
        return [
            'assumptions' => self::TYPE_ASSUMPTION,
            'risks' => self::TYPE_RISK,
            'opportunities' => self::TYPE_OPPORTUNITY,
            'learning_objectives' => self::TYPE_LEARNING_OBJECTIVE,
            'leadership_questions' => self::TYPE_LEADERSHIP_QUESTION,
        ];
    }
}
