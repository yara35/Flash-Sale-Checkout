<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'available_stock'];

    public function holds(){
        return $this->hasMany(Hold::class);
    }
    
}
