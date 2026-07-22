<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QbrEvidenceSnapshot extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_qbr_evidence_snapshots';

    protected $fillable = ['qbr_id', 'snapshot', 'captured_at'];

    protected $casts = [
        'snapshot' => 'array',
        'captured_at' => 'datetime',
    ];

    public function qbr()
    {
        return $this->belongsTo(Qbr::class, 'qbr_id');
    }
}
