<?php

namespace Kezi\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $bom_id
 * @property int $product_id
 * @property float $quantity
 * @property \Brick\Money\Money $unit_cost
 * @property string $currency_code
 * @property int|null $work_center_id
 * @property-read \App\Models\Company $company
 * @property-read \Kezi\Manufacturing\Models\BillOfMaterial $billOfMaterial
 * @property-read \Kezi\Product\Models\Product|null $product
 */
class BOMLine extends Model
{
    use HasFactory;

    protected $table = 'bom_lines';

    protected static function newFactory(): \Kezi\Manufacturing\Database\Factories\BOMLineFactory
    {
        return \Kezi\Manufacturing\Database\Factories\BOMLineFactory::new();
    }

    protected $fillable = [
        'company_id',
        'bom_id',
        'product_id',
        'quantity',
        'unit_cost',
        'currency_code',
        'work_center_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
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
     * @return BelongsTo<BillOfMaterial, static>
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<WorkCenter, static>
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }
}
