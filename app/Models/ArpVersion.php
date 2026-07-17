<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArpVersion extends Model
{
    use HasFactory;

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_PUBLISHED = 'published';

    public $timestamps = false;

    protected $table = 'wp_fusion_arp_versions';

    protected $fillable = ['arp_id', 'version', 'status', 'snapshot', 'published_by_user_id', 'published_at', 'created_at'];

    protected $casts = [
        'snapshot' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'version' => 'decimal:1',
    ];

    public function arp()
    {
        return $this->belongsTo(Arp::class, 'arp_id');
    }
}
