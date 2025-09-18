<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockQuant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'lot_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
        'reserved_quantity' => 'float',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * @return BelongsTo<Lot, static>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    /**
     * Get available quantity (quantity - reserved_quantity)
     */
    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Scope to get quants for a specific lot
     */
    public function scopeForLot($query, ?int $lotId)
    {
        if ($lotId === null) {
            return $query->whereNull('lot_id');
        }

        return $query->where('lot_id', $lotId);
    }

    /**
     * Scope to get quants with available quantity
     */
    public function scopeWithAvailableQuantity($query)
    {
        return $query->whereRaw('quantity > reserved_quantity');
    }
}
