<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpGfAddonFeed extends Model
{
    protected $table = 'wp_gf_addon_feed';
    use HasFactory;
    protected $guarded=[];
}
