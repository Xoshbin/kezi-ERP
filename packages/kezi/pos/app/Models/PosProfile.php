<?php

namespace Kezi\Pos\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $type
 * @property array<string, mixed> $features
 * @property array<string, mixed> $settings
 * @property bool $is_active
 * @property int|null $stock_location_id
 * @property int|null $default_income_account_id
 * @property int|null $default_payment_journal_id
 * @property-read \App\Models\Company $company
 * @property-read \Kezi\Inventory\Models\StockLocation|null $stockLocation
 */
class PosProfile extends Model
{
    /** @use HasFactory<\Kezi\Pos\Database\Factories\PosProfileFactory> */
    use HasFactory;

    protected static function newFactory(): \Kezi\Pos\Database\Factories\PosProfileFactory
    {
        return \Kezi\Pos\Database\Factories\PosProfileFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'features',
        'settings',
        'is_active',
        'stock_location_id',
        'default_income_account_id',
        'default_payment_journal_id',
        'return_policy',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'settings' => 'array',
            'return_policy' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(PosResource::class);
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(\Kezi\Inventory\Models\StockLocation::class);
    }

    public function defaultIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(\Kezi\Accounting\Models\Account::class, 'default_income_account_id');
    }

    public function defaultPaymentJournal(): BelongsTo
    {
        return $this->belongsTo(\Kezi\Accounting\Models\Journal::class, 'default_payment_journal_id');
    }
}
