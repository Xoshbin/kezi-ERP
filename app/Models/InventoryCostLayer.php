<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryCostLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'cost_per_unit',
        'remaining_quantity',
        'purchase_date',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'cost_per_unit' => MoneyCast::class,
        'purchase_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * This robust implementation prevents N+1 query issues.
     */
    public function getCurrencyIdAttribute(): int
    {
        // If the product relationship is loaded, use it. If not, lazy-load it.
        return $this->product->currency_id ?? $this->product()->first()->currency_id;
    }
}
