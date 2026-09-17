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
    // Pinned explicitly: WpGfForm::formViews() (connection 'wordpress',
    // prefix wp_) would otherwise inherit its connection into this model
    // when eager-loading, double-prefixing the already wp_-prefixed table
    // name into wp_wp_gf_form_view — the same bug fixed on Company.
    protected $connection = 'mysql';

    protected $table='wp_gf_form_view';
    use HasFactory;
    protected $fillable=['form_id', 'date_created', 'ip', 'count'];
    public function wpGfForm()
    {
        return $this->belongsTo(WpGfForm::class,'form_id');
    }
}
