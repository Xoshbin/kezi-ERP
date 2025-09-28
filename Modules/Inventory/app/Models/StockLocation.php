<?php

namespace Modules\Inventory\Models;


use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\StockLocationFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\Inventory\StockLocationType;

class StockLocation extends Model
{
    /** @use HasFactory<StockLocationFactory> */
    use HasFactory;

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
