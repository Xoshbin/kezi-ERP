<?php

namespace Modules\Manufacturing\Models;

use App\Casts\MoneyCast;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\StockMove;
use Modules\Product\Models\Product;

class ManufacturingOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'manufacturing_order_id',
        'product_id',
        'quantity_required',
        'quantity_consumed',
        'unit_cost',
        'currency_code',
        'stock_move_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:4',
            'quantity_consumed' => 'decimal:4',
            'unit_cost' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ManufacturingOrder, static>
     */
    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockMove, static>
     */
    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }
}
