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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Manufacturing\Models\WorkCenter|null $workCenter
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereBomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BOMLine whereWorkCenterId($value)
 * @method static \Kezi\Manufacturing\Database\Factories\BOMLineFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
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
