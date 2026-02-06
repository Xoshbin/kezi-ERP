<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Inventory\Enums\Inventory\ReorderingRoute;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $location_id
 * @property int $reordering_rule_id
 * @property float $suggested_qty
 * @property string $priority
 * @property ReorderingRoute $route
 * @property string $reason
 * @property string|null $origin_reference
 * @property \Illuminate\Support\Carbon $suggested_order_date
 * @property \Illuminate\Support\Carbon $expected_delivery_date
 * @property bool $processed
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Inventory\Models\StockLocation $location
 * @property-read Product $product
 * @property-read \Kezi\Inventory\Models\ReorderingRule $reorderingRule
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion byPriority(string $priority)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion unprocessed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereExpectedDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereOriginReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereProcessed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereReorderingRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereSuggestedOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereSuggestedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReplenishmentSuggestion whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ReplenishmentSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'reordering_rule_id',
        'suggested_qty',
        'priority',
        'route',
        'reason',
        'origin_reference',
        'suggested_order_date',
        'expected_delivery_date',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'suggested_qty' => 'float',
        'route' => ReorderingRoute::class,
        'suggested_order_date' => 'date',
        'expected_delivery_date' => 'date',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

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
     * @return BelongsTo<StockLocation, static>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * @return BelongsTo<ReorderingRule, static>
     */
    public function reorderingRule(): BelongsTo
    {
        return $this->belongsTo(ReorderingRule::class);
    }

    /**
     * Scope to get unprocessed suggestions
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope to get suggestions by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Mark suggestion as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'green',
            default => 'gray',
        };
    }
}
