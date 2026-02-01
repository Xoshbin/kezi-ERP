<?php

namespace Jmeryar\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Enums\Inventory\SerialNumberStatus;
use Jmeryar\Product\Models\Product;

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

    protected static function newFactory(): \Jmeryar\Inventory\Database\Factories\SerialNumberFactory
    {
        return \Jmeryar\Inventory\Database\Factories\SerialNumberFactory::new();
    }
}
