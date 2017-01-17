<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $fillable = ['name', 'description', 'package_id'];

    public function setPackageIdAttribute($value)
    {
        $this->attributes['package_id'] = $value;
        $this->attributes['private_key'] = bcrypt($value);
        if(!isset($this->attributes['public_key'])){
            $this->attributes['public_key'] = sha1(md5($this->attributes['private_key']) . "||" . $this->attributes['package_id']);
        }
    }

    public function scopeHasPublicKey($query, $key)
    {
        return $query->where('public_key', $key);
    }

    /**
     * Get the post that owns the comment.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
