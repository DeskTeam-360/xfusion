<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
    protected $table = 'cache';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['key', 'value', 'expiration'];

    public function set($key, $value, $minutes = 60)
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiration = now()->addMinutes($minutes);
        $this->save();
    }

    public function get($key)
    {
        return $this->where('key', $key)->first();
    }

    public function delete($key)
    {
        return $this->where('key', $key)->delete();
    }

    public function flush()
    {
        return $this->truncate();
    }

    public function has($key)
    {
        return $this->where('key', $key)->exists();
    }
}
