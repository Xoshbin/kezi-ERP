<?php

namespace Modules\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'lot_code',
        'expiration_date',
        'active',
        'is_rejected',
        'rejection_reason',
        'quarantine_location_id',
    ];

    protected $casts = [
        'expiration_date' => 'date',
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
     * @return HasMany<StockQuant, static>
     */
    public function stockQuants(): HasMany
    {
        return $this->hasMany(StockQuant::class);
    }

    /**
     * @return HasMany<StockMoveLine, static>
     */
    public function stockMoveLines(): HasMany
    {
        return $this->hasMany(StockMoveLine::class);
    }

    /**
     * @return HasMany<\Modules\QualityControl\Models\QualityCheck, static>
     */
    public function qualityChecks(): HasMany
    {
        return $this->hasMany(\Modules\QualityControl\Models\QualityCheck::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Modules\Inventory\Models\StockLocation, static>
     */
    public function quarantineLocation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'quarantine_location_id');
    }

    /**
     * Check if the lot is rejected
     */
    public function isRejected(): bool
    {
        return $this->is_rejected ?? false;
    }

    /**
     * Check if the lot is expired
     */
    public function isExpired(): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isPast();
    }

    /**
     * Get days until expiration (negative if expired)
     */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->expiration_date) {
            return null;
        }

        return now()->diffInDays($this->expiration_date, false);
    }

    /**
     * Scope to get non-expired lots
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiration_date')
                ->orWhere('expiration_date', '>', now());
        });
    }

    /**
     * Scope to get lots ordered by expiration (FEFO)
     */
    public function scopeOrderByExpiration($query)
    {
        return $query->orderByRaw('expiration_date IS NULL, expiration_date ASC');
    }

    protected static function newFactory(): \Modules\Inventory\Database\Factories\LotFactory
    {
        return \Modules\Inventory\Database\Factories\LotFactory::new();
    }
}
