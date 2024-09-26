<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseGroupBackup extends Model
{
    use HasFactory;
    protected $fillable=['season_id','wp_gf_form_id'];

    public function season() {
        return $this->belongsTo(Season::class, 'season_id');
    }
    public function wp_gf_form() {
        return $this->belongsTo(WpGfForm::class, 'wp_gf_form_id');
    }
}
