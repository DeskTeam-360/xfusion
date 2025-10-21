<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $meta_id
 * @property integer $post_id
 * @property string $meta_key
 * @property string $meta_value
 */

class WpPostMeta extends Model
{

    public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'meta_id',
        'post_id',
        'meta_key',
        'meta_value',
    ];

    public function wpPost()
    {
        return $this->belongsTo(WpPost::class,'post_id');
    }


}
