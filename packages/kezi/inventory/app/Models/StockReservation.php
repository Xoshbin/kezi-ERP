<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $stock_move_id
 * @property int $location_id
 * @property numeric $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Inventory\Models\StockLocation $location
 * @property-read \Kezi\Inventory\Models\StockMove $move
 * @property-read Product $product
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereStockMoveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'stock_move_id',
        'location_id',
        'quantity',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function move(): BelongsTo
    {
        return $this->belongsTo(StockMove::class, 'stock_move_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }
}
