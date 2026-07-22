<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QbrKpi extends Model
{
    use HasFactory;

    public const STATUS_ON_TRACK = 'on_track';

    public const STATUS_AT_RISK = 'at_risk';

    public const STATUS_OFF_TRACK = 'off_track';

    protected $table = 'wp_fusion_qbr_kpis';

    protected $fillable = [
        'qbr_id',
        'name',
        'current_value',
        'target_value',
        'status',
        'trend',
        'priority_rank',
    ];

    public function qbr()
    {
        return $this->belongsTo(Qbr::class, 'qbr_id');
    }
}
