<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    // Esto permite que Filament pueda guardar estos datos
    protected $fillable = [
        'image_path', 
        'is_active'
    ];
}