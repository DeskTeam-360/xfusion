<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @property integer $id
 * @property integer $form_id
 * @property string $date_created
 * @property string $ip
 * @property string $count
 * @property WpGfForm $wpGfForm
 *
 */
class WpGfFormView extends Model
{
    protected $table='wp_gf_form_view';
    use HasFactory;
    protected $fillable=['form_id', 'date_created', 'ip', 'count'];
    public function wpGfForm()
    {
        return $this->belongsTo(WpGfForm::class,'form_id');
    }
}
