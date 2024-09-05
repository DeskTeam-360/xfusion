<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $umeta_id
 * @property integer $user_id
 * @property string $meta_key
 * @property string $meta_value
 */

class WpUserMeta extends Model
{
    public $timestamps=false;
    protected $primaryKey = 'umeta_id';
    protected $table= 'wp_usermeta' ;
    use HasFactory;
    protected $fillable =['user_id', 'meta_key', 'meta_value'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

}
