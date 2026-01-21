<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $product_attribute_id
 * @property string $name
 */
class ProductAttributeValue extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'product_attribute_id',
        'name',
        'color_code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public array $translatable = ['name'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): \Modules\Product\Database\Factories\ProductAttributeValueFactory
    {
        return \Modules\Product\Database\Factories\ProductAttributeValueFactory::new();
    }
}
