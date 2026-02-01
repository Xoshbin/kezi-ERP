<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property int $asset_account_id
 * @property int $accumulated_depreciation_account_id
 * @property int $depreciation_expense_account_id
 * @property DepreciationMethod $depreciation_method
 * @property int $useful_life_years
 * @property float|null $salvage_value_default
 * @property bool $prorata_temporis
 * @property float|null $declining_factor
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AssetCategory extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'depreciation_method',
        'useful_life_years',
        'salvage_value_default',
        'prorata_temporis',
        'declining_factor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'prorata_temporis' => 'boolean',
        'declining_factor' => 'double',
        'depreciation_method' => DepreciationMethod::class,
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }
}
