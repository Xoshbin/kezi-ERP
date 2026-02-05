<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string $lot_code
 * @property \Illuminate\Support\Carbon|null $expiration_date
 * @property bool $active
 * @property int $is_rejected
 * @property string|null $rejection_reason
 * @property int|null $quarantine_location_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityCheck> $qualityChecks
 * @property-read int|null $quality_checks_count
 * @property-read \Kezi\Inventory\Models\StockLocation|null $quarantineLocation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Inventory\Models\StockMoveLine> $stockMoveLines
 * @property-read int|null $stock_move_lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Inventory\Models\StockQuant> $stockQuants
 * @property-read int|null $stock_quants_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot notExpired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot orderByExpiration()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereIsRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereLotCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereQuarantineLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lot whereUpdatedAt($value)
 * @method static \Kezi\Inventory\Database\Factories\LotFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
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
     * @return HasMany<\Kezi\QualityControl\Models\QualityCheck, static>
     */
    public function qualityChecks(): HasMany
    {
        return $this->hasMany(\Kezi\QualityControl\Models\QualityCheck::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Kezi\Inventory\Models\StockLocation, static>
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

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\LotFactory
    {
        return \Kezi\Inventory\Database\Factories\LotFactory::new();
    }
}
