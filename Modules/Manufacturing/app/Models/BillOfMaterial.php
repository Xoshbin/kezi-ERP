<?php

namespace Modules\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Product\Models\Product;
use Spatie\Translatable\HasTranslations;

class BillOfMaterial extends Model
{
    use HasFactory;
    use HasTranslations;

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
