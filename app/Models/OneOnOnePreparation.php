<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOnePreparation extends Model
{
    use HasFactory;

    public const ROLE_EMPLOYEE = 'employee';

    public const ROLE_LEADER = 'leader';

    protected $table = 'wp_fusion_one_on_one_preparations';

    protected $fillable = ['conversation_id', 'author_role', 'author_user_id', 'content', 'is_revealed', 'revealed_at'];

    protected $casts = [
        'is_revealed' => 'boolean',
        'revealed_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(OneOnOneConversation::class, 'conversation_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public static function validRoles(): array
    {
        return [self::ROLE_EMPLOYEE, self::ROLE_LEADER];
    }
}
