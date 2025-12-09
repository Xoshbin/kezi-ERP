<?php

namespace Modules\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Product\Models\Product;

class StockMoveProductLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'stock_move_id',
        'product_id',
        'quantity',
        'from_location_id',
        'to_location_id',
        'description',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<StockMove, static>
     */
    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
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
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    /**
     * @return MorphTo<Model, static>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<StockMoveLine, static>
     */
    public function stockMoveLines(): HasMany
    {
        return $this->hasMany(StockMoveLine::class);
    }

    /**
     * Get the total quantity allocated to lots for this product line
     */
    public function getAllocatedQuantity(): float
    {
        return $this->stockMoveLines()->sum('quantity');
    }

    /**
     * Get the remaining quantity that needs to be allocated to lots
     */
    public function getRemainingQuantity(): float
    {
        return $this->quantity - $this->getAllocatedQuantity();
    }

    /**
     * Check if this product line is fully allocated to lots
     */
    public function isFullyAllocated(): bool
    {
        return $this->getRemainingQuantity() <= 0;
    }

    protected static function newFactory(): \Modules\Inventory\Database\Factories\StockMoveProductLineFactory
    {
        return \Modules\Inventory\Database\Factories\StockMoveProductLineFactory::new();
    }
}
