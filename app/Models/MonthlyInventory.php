<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'snapshot_data',      // Array JSON con el estado de todos los productos y stocks
        'total_products',
        'total_stock',
        'total_value',
        'closed_by_user_id',
    ];

    protected $casts = [
        'snapshot_data' => 'array', // Deserializa el JSON automáticamente a array
        'year' => 'integer',
        'month' => 'integer',
        'total_products' => 'integer',
        'total_stock' => 'integer',
        'total_value' => 'decimal:2',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
