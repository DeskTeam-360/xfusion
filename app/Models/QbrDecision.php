<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QbrDecision extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_qbr_decisions';

    protected $fillable = [
        'qbr_id',
        'decision',
        'owner_user_id',
        'owner_name',
        'impact_area',
        'next_step',
        'target_date',
        'priority_rank',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function qbr()
    {
        return $this->belongsTo(Qbr::class, 'qbr_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
