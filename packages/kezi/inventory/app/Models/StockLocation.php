<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Inventory\Enums\Inventory\StockLocationType;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property StockLocationType $type
 * @property bool $is_active
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockLocation> $children
 * @property-read int|null $children_count
 * @property-read Company $company
 * @property-read StockLocation|null $parent
 *
 * @method static Builder<static>|StockLocation newModelQuery()
 * @method static Builder<static>|StockLocation newQuery()
 * @method static Builder<static>|StockLocation ofType(\Kezi\Inventory\Enums\Inventory\StockLocationType $type)
 * @method static Builder<static>|StockLocation query()
 * @method static Builder<static>|StockLocation whereCompanyId($value)
 * @method static Builder<static>|StockLocation whereCreatedAt($value)
 * @method static Builder<static>|StockLocation whereId($value)
 * @method static Builder<static>|StockLocation whereIsActive($value)
 * @method static Builder<static>|StockLocation whereName($value)
 * @method static Builder<static>|StockLocation whereParentId($value)
 * @method static Builder<static>|StockLocation whereType($value)
 * @method static Builder<static>|StockLocation whereUpdatedAt($value)
 * @method static \Kezi\Inventory\Database\Factories\StockLocationFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class StockLocation extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\StockLocationFactory
    {
        return \Kezi\Inventory\Database\Factories\StockLocationFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => StockLocationType::class,
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'parent_id');
    }

    /**
     * @return HasMany<StockLocation, static>
     */
    public function children(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'parent_id');
    }

    /**
     * Scope a query to only include locations of a specific type.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOfType($query, StockLocationType $type)
    {
        return $query->where('type', $type);
    }
}
