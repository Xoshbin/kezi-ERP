<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\Inventory\StockLocationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'parent_id');
    }

    /**
     * Scope a query to only include locations of a specific type.
     *
     * @param Builder $query
     * @param StockLocationType $type
     * @return Builder
     */
    public function scopeOfType($query, StockLocationType $type)
    {
        return $query->where('type', $type);
    }
}
