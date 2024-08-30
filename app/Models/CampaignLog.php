<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int user_id
 * @property int tag_id
 * @property string status
 */
class CampaignLog extends Model
{
    use HasFactory;
    protected $fillable=['user_id', 'tag_id', 'status'];
}
