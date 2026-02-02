<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;

/**
 * @property int $id
 * @property int $company_id
 * @property int $landed_cost_id
 * @property int $stock_move_id
 * @property \Brick\Money\Money $additional_cost
 * @property-read Company $company
 * @property-read LandedCost $landedCost
 * @property-read StockMove $stockMove
 */
class LandedCostLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'landed_cost_id',
        'stock_move_id',
        'additional_cost',
    ];

    protected $casts = [
        'additional_cost' => BaseCurrencyMoneyCast::class,
    ];

    protected $with = ['company.currency'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function landedCost(): BelongsTo
    {
        return $this->belongsTo(LandedCost::class);
    }

    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }
}
