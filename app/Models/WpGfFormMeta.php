<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @property integer $form_id
 * @property string $display_meta
 * @property string $entries_grid_meta
 * @property string $confirmations
 * @property string $notifications
 * @property WpGfForm $wpGfForm
 * @property object $getFields
 * @property array $getField
 * @property string $getFieldLabel
 * @property string getFieldType
 */
class WpGfFormMeta extends Model
{
    protected $table='wp_gf_form_meta';
    use HasFactory;
    protected $fillable=['form_id', 'display_meta', 'entries_grid_meta', 'confirmations', 'notifications'];

    public function wpGfForm()
    {
        return $this->belongsTo(WpGfForm::class,'form_id');
    }

    public  function getFields()
    {
        return json_decode($this->display_meta);
    }
    public  function getField($index)
    {
        return json_decode($this->display_meta)->fields[$index];
    }

    public  function getFieldType($index)
    {
        return json_decode($this->display_meta)->fields[$index]->type;
    }
    public function getFieldLabel($index)
    {

        return json_decode($this->display_meta)->fields[$index]->label;
    }
}
