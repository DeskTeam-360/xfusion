<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $ID
 * @property integer $post_author
 * @property integer $post_date
 * @property integer $post_date_gmt
 * @property integer $post_content
 * @property integer $post_title
 * @property integer $post_excerpt
 * @property integer $post_status
 * @property integer $comment_status
 * @property integer $ping_status
 *  @property integer $post_password
 *  @property integer $post_name
 *  @property integer $to_ping
 *  @property integer $pinged
 *  @property integer $post_modified
 *  @property integer $post_modified_gmt
 *  @property integer $post_content_filtered
 *  @property integer $post_parent
 *  @property integer $guid
 * @property integer $menu_order
 * @property integer $post_type
 * @property integer $post_mime_type
 * @property integer $comment_count
 */

class WpPost extends Model
{

    public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'ID',
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'post_author');
    }


}
