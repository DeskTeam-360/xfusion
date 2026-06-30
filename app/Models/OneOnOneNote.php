<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneOnOneNote extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_one_on_one_notes';

    protected $fillable = ['conversation_id', 'section', 'note', 'created_by'];

    public function conversation()
    {
        return $this->belongsTo(OneOnOneConversation::class, 'conversation_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
