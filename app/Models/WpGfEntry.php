<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $form_id
 * @property integer $post_id
 * @property string $date_created
 * @property string $date_updated
 * @property integer $is_starred
 * @property integer $is_read
 * @property string $ip
 * @property string $source_url
 * @property string $user_agent
 * @property string $currency
 * @property string $payment_status
 * @property string $payment_date
 * @property string $payment_amount
 * @property string $payment_method
 * @property string $transaction_id
 * @property integer $is_fulfilled
 * @property integer $created_by
 * @property integer $transaction_type
 * @property string $status
 * @property WpGfForm $wpGfForm
 * @property WpGfEntryMeta[] $wpGfEntryMetas
 */

class WpGfEntry extends Model
{
    protected $table='wp_gf_entry';
    public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'form_id', 'post_id',
        'date_created', 'date_updated',
        'is_starred', 'is_read',
        'ip', 'source_url', 'user_agent','currency',
        'payment_status','payment_date','payment_amount', 'payment_method',
        'is_fulfilled','transaction_id','created_by', 'transaction_type',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function wpGfForm()
    {
        return $this->belongsTo(WpGfForm::class,'form_id');
    }

    public function wpGfEntryMetas()
    {
        return $this->hasMany(WpGfEntryMeta::class,'entry_id');
    }

}
