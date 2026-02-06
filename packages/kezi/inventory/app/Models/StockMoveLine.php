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
 * @property int $stock_move_product_line_id
 * @property int|null $lot_id
 * @property int|null $serial_number_id
 * @property float $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Inventory\Models\Lot|null $lot
 * @property-read \Kezi\Inventory\Models\SerialNumber|null $serialNumber
 * @property-read \Kezi\Inventory\Models\StockMoveProductLine $stockMoveProductLine
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereLotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereSerialNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereStockMoveProductLineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StockMoveLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'stock_move_product_line_id',
        'lot_id',
        'serial_number_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<StockMoveProductLine, static>
     */
    public function stockMoveProductLine(): BelongsTo
    {
        return $this->belongsTo(StockMoveProductLine::class);
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
     * Get the stock move through the product line
     */
    public function stockMove(): StockMove
    {
        return $this->stockMoveProductLine->stockMove;
    }

    /**
     * Get the product through the product line
     */
    public function product(): Product
    {
        return $this->stockMoveProductLine->product;
    }
}
