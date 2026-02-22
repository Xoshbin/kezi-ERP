<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Inventory\Observers\StockQuantObserver;
use Kezi\Product\Models\Product;

#[ObservedBy(StockQuantObserver::class)]
/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $location_id
 * @property int|null $lot_id
 * @property int|null $serial_number_id
 * @property float $quantity
 * @property float $reserved_quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read float $available_quantity
 * @property-read \Kezi\Inventory\Models\StockLocation $location
 * @property-read \Kezi\Inventory\Models\Lot|null $lot
 * @property-read Product $product
 * @property-read \Kezi\Inventory\Models\SerialNumber|null $serialNumber
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant forLot(?int $lotId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereLotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereReservedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereSerialNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockQuant withAvailableQuantity()
 * @method static \Kezi\Inventory\Database\Factories\StockQuantFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class StockQuant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'lot_id',
        'serial_number_id',
        'quantity',
        'reserved_quantity',
    ];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = ['product'];

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
     * @return BelongsTo<SerialNumber, static>
     */
    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
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

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\StockQuantFactory
    {
        return \Kezi\Inventory\Database\Factories\StockQuantFactory::new();
    }
}
