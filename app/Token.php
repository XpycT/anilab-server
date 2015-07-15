<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $fillable = ['name','description','package_id'];

    public function setPackageIdAttribute($value)
    {
        $this->attributes['package_id'] = $value;

        if (! $this->attributes['private_key']) {
            $this->attributes['private_key'] = md5(bcrypt($value));
        }
    }
}
