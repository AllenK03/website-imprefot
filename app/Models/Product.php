<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Esto permite que Filament pueda guardar estos datos
    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'price', 
        'stock', 
        'image', 
        'is_active'
    ];
}