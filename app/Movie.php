<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = ['movie_id','title','description','info','service'];

    protected $hidden = ['service'];

    protected $casts = [
        'info' => 'array',
    ];
}
