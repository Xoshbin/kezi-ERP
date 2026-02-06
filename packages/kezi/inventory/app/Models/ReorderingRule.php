<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Inventory\Enums\Inventory\ReorderingRoute;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $location_id
 * @property float $min_qty
 * @property float $max_qty
 * @property float $safety_stock
 * @property float $multiple
 * @property ReorderingRoute $route
 * @property int $lead_time_days
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Inventory\Models\StockLocation $location
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Inventory\Models\ReplenishmentSuggestion> $replenishmentSuggestions
 * @property-read int|null $replenishment_suggestions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereLeadTimeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereMaxQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereMinQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereMultiple($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereSafetyStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReorderingRule whereUpdatedAt($value)
 * @method static \Kezi\Inventory\Database\Factories\ReorderingRuleFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class ReorderingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'min_qty',
        'max_qty',
        'safety_stock',
        'multiple',
        'route',
        'lead_time_days',
        'active',
    ];

    protected $casts = [
        'min_qty' => 'float',
        'max_qty' => 'float',
        'safety_stock' => 'float',
        'multiple' => 'float',
        'route' => ReorderingRoute::class,
        'lead_time_days' => 'integer',
        'active' => 'boolean',
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
     * @return HasMany<ReplenishmentSuggestion, static>
     */
    public function replenishmentSuggestions(): HasMany
    {
        return $this->hasMany(ReplenishmentSuggestion::class);
    }

    /**
     * Scope to get active rules
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Calculate suggested quantity based on current stock and rule parameters
     */
    public function calculateSuggestedQuantity(float $currentQty): float
    {
        if ($this->route === ReorderingRoute::MTO) {
            // For MTO, quantity is determined by specific demand
            return 0;
        }

        $neededQty = $this->max_qty - $currentQty;

        if ($neededQty <= 0) {
            return 0;
        }

        // Round up to next multiple
        if ($this->multiple > 1) {
            $neededQty = ceil($neededQty / $this->multiple) * $this->multiple;
        }

        return $neededQty;
    }

    /**
     * Determine priority based on current stock level
     */
    public function determinePriority(float $availableQty): string
    {
        if ($this->route === ReorderingRoute::MTO) {
            return 'urgent';
        }

        if ($availableQty < $this->safety_stock) {
            return 'high';
        }

        return 'normal';
    }

    /**
     * Generate reason text for replenishment
     */
    public function generateReason(float $currentQty, float $availableQty): string
    {
        if ($this->route === ReorderingRoute::MTO) {
            return 'Make-to-Order replenishment required';
        }

        if ($availableQty < $this->safety_stock) {
            return "Below safety stock level ({$this->safety_stock} units). Current available: {$availableQty}";
        }

        if ($currentQty < $this->min_qty) {
            return "Below minimum quantity ({$this->min_qty} units). Current stock: {$currentQty}";
        }

        return 'Stock replenishment suggested';
    }

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\ReorderingRuleFactory
    {
        return \Kezi\Inventory\Database\Factories\ReorderingRuleFactory::new();
    }
}
