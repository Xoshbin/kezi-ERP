<?php

namespace Jmeryar\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jmeryar\Manufacturing\Enums\BOMType;
use Jmeryar\Product\Models\Product;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string $code
 * @property string $name
 * @property \Jmeryar\Manufacturing\Enums\BOMType $type
 * @property float $quantity
 * @property bool $is_active
 * @property string|null $notes
 * @property-read \App\Models\Company $company
 * @property-read \Jmeryar\Product\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\Jmeryar\Manufacturing\Models\BOMLine[] $lines
 */
class BillOfMaterial extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $table = 'bills_of_materials';

    protected static function newFactory(): \Jmeryar\Manufacturing\Database\Factories\BillOfMaterialFactory
    {
        return \Jmeryar\Manufacturing\Database\Factories\BillOfMaterialFactory::new();
    }

    protected $fillable = [
        'company_id',
        'product_id',
        'code',
        'name',
        'type',
        'quantity',
        'is_active',
        'notes',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'type' => BOMType::class,
            'quantity' => 'decimal:4',
            'is_active' => 'boolean',
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
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<BOMLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(BOMLine::class, 'bom_id');
    }

    /**
     * @return HasMany<ManufacturingOrder, static>
     */
    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class, 'bom_id');
    }
}
