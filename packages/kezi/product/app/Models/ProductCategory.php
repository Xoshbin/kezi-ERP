<?php

namespace Kezi\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductCategory> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductCategory> $descendants
 * @property-read int|null $descendants_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'parent_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductCategory $category) {
            if ($category->isDirty('parent_id') && $category->parent_id) {
                // Prevent self-referencing
                if ($category->getKey() && $category->parent_id == $category->getKey()) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'A category cannot be its own parent.',
                    ]);
                }

                // Cycle detection: Walk up the hierarchy from the new parent
                if ($category->exists) {
                    /** @var ProductCategory|null $ancestor */
                    $ancestor = ProductCategory::find($category->parent_id);
                    while ($ancestor) {
                        if ($ancestor->getKey() == $category->getKey()) {
                            throw ValidationException::withMessages([
                                'parent_id' => 'Circular dependency detected. A category cannot be a child of its own descendant.',
                            ]);
                        }
                        $ancestor = $ancestor->parent;
                    }
                }
            }
        });

        static::deleting(function (ProductCategory $category) {
            // Prevent deletion if children exist
            if ($category->children()->exists()) {
                throw ValidationException::withMessages([
                    'id' => 'Cannot delete capability with sub-categories. Please delete or move them first.',
                ]);
            }
        });
    }

    /**
     * @return BelongsTo<ProductCategory, static>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * @return HasMany<ProductCategory, static>
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /**
     * Recursive relationship for descendants
     *
     * @return HasMany<ProductCategory, static>
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
}
