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
 * @property-read \Kezi\Accounting\Models\Account $accumulatedDepreciationAccount
 * @property-read \Kezi\Accounting\Models\Account $assetAccount
 * @property-read Company $company
 * @property-read \Kezi\Accounting\Models\Account $depreciationExpenseAccount
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereAccumulatedDepreciationAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereAssetAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereDecliningFactor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereDepreciationExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereDepreciationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereProrataTemporis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereSalvageValueDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereUsefulLifeYears($value)
 *
 * @mixin \Eloquent
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
