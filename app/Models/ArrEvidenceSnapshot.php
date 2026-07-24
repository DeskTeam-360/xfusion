<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrEvidenceSnapshot extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_arr_evidence_snapshots';

    protected $fillable = ['arr_id', 'snapshot', 'captured_at'];

    protected $casts = [
        'snapshot' => 'array',
        'captured_at' => 'datetime',
    ];

    public function arr()
    {
        return $this->belongsTo(Arr::class, 'arr_id');
    }
}
