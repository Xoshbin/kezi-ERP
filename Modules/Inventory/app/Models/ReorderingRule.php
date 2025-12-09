<?php

namespace Modules\Inventory\Models;

use App\Models\Company;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\StockLocation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\ReplenishmentSuggestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\Inventory\ReorderingRoute;

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

        return "Stock replenishment suggested";
    }

    protected static function newFactory(): \Modules\Inventory\Database\Factories\ReorderingRuleFactory
    {
        return \Modules\Inventory\Database\Factories\ReorderingRuleFactory::new();
    }
}
