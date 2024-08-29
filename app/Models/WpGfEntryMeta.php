<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $form_id
 * @property integer $entry_id
 * @property string $meta_key
 * @property string $meta_value
 * @property string $item_index
 * @property WpGfForm $wpGfForm
 * @property WpGfEntry $wpGfEntry

 */


class WpGfEntryMeta extends Model
{
    protected $table='wp_gf_entry_meta';
    use HasFactory;
    protected $fillable=['form_id','entry_id','meta_key','meta_value','item_index'];

    public function wpGfForm()
    {
        return $this->belongsTo(WpGfForm::class,'form_id');
    }

    public function wpGfEntry()
    {
        return $this->belongsTo(WpGfEntry::class,'entry_id');
    }

}
