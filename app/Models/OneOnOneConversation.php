<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOneConversation extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'wp_fusion_one_on_one_conversations';

    protected $fillable = ['one_on_one_id', 'scheduled_at', 'held_at', 'meeting_link', 'status'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'held_at' => 'datetime',
    ];

    public function oneOnOne()
    {
        return $this->belongsTo(OneOnOne::class, 'one_on_one_id');
    }

    public function brief()
    {
        return $this->hasOne(OneOnOneAiBrief::class, 'conversation_id')->latestOfMany();
    }

    public function briefs()
    {
        return $this->hasMany(OneOnOneAiBrief::class, 'conversation_id')->orderByDesc('id');
    }

    public function preparations()
    {
        return $this->hasMany(OneOnOnePreparation::class, 'conversation_id');
    }

    public function notes()
    {
        return $this->hasMany(OneOnOneNote::class, 'conversation_id');
    }

    public function commitments()
    {
        return $this->hasMany(OneOnOneCommitment::class, 'conversation_id');
    }

    public function synthesis()
    {
        return $this->hasOne(OneOnOneAiSynthesis::class, 'conversation_id')->latestOfMany();
    }

    public function syntheses()
    {
        return $this->hasMany(OneOnOneAiSynthesis::class, 'conversation_id')->orderByDesc('id');
    }

    /** Preparation for one role, regardless of reveal state — used server-side for AI calls. */
    public function preparationFor(string $role): ?OneOnOnePreparation
    {
        return $this->preparations()->where('author_role', $role)->first();
    }
}
