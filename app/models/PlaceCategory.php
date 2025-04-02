<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaceCategory extends Model
{
    protected $fillable = ['name', 'icon'];

    public function places()
    {
        return $this->hasMany(Place::class, 'category_id');
    }
} 