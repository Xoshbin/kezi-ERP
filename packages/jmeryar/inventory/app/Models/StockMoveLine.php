<?php

namespace Jmeryar\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $stock_move_product_line_id
 * @property int|null $lot_id
 * @property int|null $serial_number_id
 * @property float $quantity
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
