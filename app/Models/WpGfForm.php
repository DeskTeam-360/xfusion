<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @property integer $id
 * @property string $title
 * @property string $date_created
 * @property string $date_updated
 * @property integer $is_active
 * @property integer $is_trash
 * @property WpGfEntry[] $wpGfEntries
 * @property WpGfEntryMeta[] $wpGfEntryMetas
 * @property WpGfFormMeta[] $wpGfFormMetas
 * @property WpGfFormView[] $wpGfFormViews
 */
class WpGfForm extends Model
{
    protected $table='wp_gf_form';
    use HasFactory;
    protected $fillable=['title','date_created','date_updated','is_active', 'is_trash'];

    public function wpGfEntries()
    {
        return $this->hasMany(WpGfEntry::class,'entry_id');
    }

    public function wpGfEntryMetas()
    {
        return $this->hasMany(WpGfEntryMeta::class,'form_id');
    }
    public function wpGfFormMetas()
    {
        return $this->hasOne(WpGfFormMeta::class,'form_id');
    }
    public function wpGfFormViews()
    {
        return $this->hasMany(WpGfFormView::class,'form_id');
    }
}
