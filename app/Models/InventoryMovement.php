<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',             // 'abastecimiento', 'venta', 'ajuste_manual'
        'reason',           // Observación / Nota de entrega
        'client_id',
        'user_id',
        'total_amount',     // Agregado para permitir la asignación masiva
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryMovementItem::class);
    }
}