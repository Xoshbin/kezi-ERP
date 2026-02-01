<?php

namespace Jmeryar\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class WorkCenter extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function newFactory(): \Jmeryar\Manufacturing\Database\Factories\WorkCenterFactory
    {
        return \Jmeryar\Manufacturing\Database\Factories\WorkCenterFactory::new();
    }

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'hourly_cost',
        'currency_code',
        'capacity',
        'is_active',
        'notes',
    ];

    public array $translatable = ['name'];

    protected $casts = [
        'capacity' => 'decimal:2',
        'hourly_cost' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<BOMLine, static>
     */
    public function bomLines(): HasMany
    {
        return $this->hasMany(BOMLine::class);
    }

    /**
     * @return HasMany<WorkOrder, static>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
