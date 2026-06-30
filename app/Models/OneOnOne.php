<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOne extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'wp_fusion_one_on_ones';

    protected $fillable = ['company_id', 'leader_user_id', 'employee_user_id', 'status'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function conversations()
    {
        return $this->hasMany(OneOnOneConversation::class, 'one_on_one_id')->orderByDesc('scheduled_at');
    }

    protected static function booted(): void
    {
        static::deleting(static function (OneOnOne $pair): void {
            $conversationIds = $pair->conversations()->pluck('id');
            OneOnOneAiBrief::whereIn('conversation_id', $conversationIds)->delete();
            OneOnOnePreparation::whereIn('conversation_id', $conversationIds)->delete();
            OneOnOneNote::whereIn('conversation_id', $conversationIds)->delete();
            OneOnOneCommitment::whereIn('conversation_id', $conversationIds)->delete();
            OneOnOneAiSynthesis::whereIn('conversation_id', $conversationIds)->delete();
            $pair->conversations()->delete();
        });
    }
}
