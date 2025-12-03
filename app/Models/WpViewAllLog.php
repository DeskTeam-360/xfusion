<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $log_time
 * @property string $source
 * @property string $note
 * @property string $reference
 * @property \App\Models\WpGfEntry $wpGfEntry
 * @property User $user
 */

class WpViewAllLog extends Model
{
    // protected $table='wp_view_all_log';
    use HasFactory;
    protected $fillable=['id', 'user_id', 'log_time', 'source', 'note','reference'];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function wpGfEntry()
    {
        return $this->belongsTo(WpGfEntry::class,'reference');
    }
}
