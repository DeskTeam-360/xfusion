<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrrEvidenceSnapshot extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_360_evidence_snapshots';

    protected $fillable = ['review_id', 'snapshot', 'captured_at'];

    protected $casts = [
        'snapshot' => 'array',
        'captured_at' => 'datetime',
    ];

    public function review()
    {
        return $this->belongsTo(IrrReview::class, 'review_id');
    }
}
