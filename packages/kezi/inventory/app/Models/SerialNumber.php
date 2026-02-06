<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\SerialNumberStatus;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string $serial_code
 * @property SerialNumberStatus $status
 * @property int|null $current_location_id
 * @property \Illuminate\Support\Carbon|null $warranty_start
 * @property \Illuminate\Support\Carbon|null $warranty_end
 * @property string|null $notes
 * @property int|null $sold_to_partner_id
 * @property \Illuminate\Support\Carbon|null $sold_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Inventory\Models\StockLocation|null $currentLocation
 * @property-read Product $product
 * @property-read Partner|null $soldToPartner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Inventory\Models\StockMoveLine> $stockMoveLines
 * @property-read int|null $stock_move_lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Inventory\Models\StockQuant> $stockQuants
 * @property-read int|null $stock_quants_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber atLocation(int $locationId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber forProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber warrantyExpiringWithin(int $days)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereCurrentLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereSerialCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereSoldAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereSoldToPartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereWarrantyEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereWarrantyStart($value)
 * @method static \Kezi\Inventory\Database\Factories\SerialNumberFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class SerialNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'serial_code',
        'status',
        'current_location_id',
        'warranty_start',
        'warranty_end',
        'notes',
        'sold_to_partner_id',
        'sold_at',
    ];

    protected $casts = [
        'status' => SerialNumberStatus::class,
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'sold_at' => 'datetime',
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
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'current_location_id');
    }

    /**
     * @return BelongsTo<Partner, static>
     */
    public function soldToPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'sold_to_partner_id');
    }

    /**
     * @return HasMany<StockMoveLine, static>
     */
    public function stockMoveLines(): HasMany
    {
        return $this->hasMany(StockMoveLine::class);
    }

    /**
     * @return HasMany<StockQuant, static>
     */
    public function stockQuants(): HasMany
    {
        return $this->hasMany(StockQuant::class);
    }

    /**
     * Check if the serial number is under warranty
     */
    public function isUnderWarranty(): bool
    {
        if (! $this->warranty_end) {
            return false;
        }

        return $this->warranty_end->isFuture();
    }

    /**
     * Get days until warranty expiration (negative if expired)
     */
    public function daysUntilWarrantyExpiration(): ?int
    {
        if (! $this->warranty_end) {
            return null;
        }

        return now()->diffInDays($this->warranty_end, false);
    }

    /**
     * Scope to get available serial numbers
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', SerialNumberStatus::Available);
    }

    /**
     * Scope to get serial numbers for a specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get serial numbers at a specific location
     */
    public function scopeAtLocation($query, int $locationId)
    {
        return $query->where('current_location_id', $locationId);
    }

    /**
     * Scope to get serial numbers with warranty expiring within days
     */
    public function scopeWarrantyExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('warranty_end')
            ->whereBetween('warranty_end', [now(), now()->addDays($days)]);
    }

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\SerialNumberFactory
    {
        return \Kezi\Inventory\Database\Factories\SerialNumberFactory::new();
    }
}
