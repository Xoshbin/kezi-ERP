<?php

namespace App\Models;

use App\Enums\Inventory\ReorderingRoute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
