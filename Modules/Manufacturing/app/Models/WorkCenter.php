<?php

namespace Modules\Manufacturing\Models;

use App\Casts\MoneyCast;
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

    protected function casts(): array
    {
        return [
            'hourly_cost' => MoneyCast::class,
            'capacity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

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
