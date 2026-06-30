<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOneCommitment extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    protected $table = 'wp_fusion_one_on_one_commitments';

    protected $fillable = [
        'conversation_id', 'title', 'description', 'owner_role', 'owner_user_id', 'status', 'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function conversation()
    {
        return $this->belongsTo(OneOnOneConversation::class, 'conversation_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
