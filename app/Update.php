<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Update extends Model
{
    protected $fillable = ['version_code', 'version_name', 'content'];

    protected $hidden = ['id','created_at','updated_at'];
}
