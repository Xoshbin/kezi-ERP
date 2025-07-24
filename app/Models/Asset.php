<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Asset
 *
 * @package App\Models
 * 
 * This Eloquent model represents a Fixed Asset within the accounting system.
 * It's designed to track long-term tangible assets, their acquisition, depreciation,
 * and eventual disposal, directly impacting the company's financial statements.
 * @property int $id
 * @property int $company_id
 * @property int $asset_account_id
 * @property int $depreciation_expense_account_id
 * @property int $accumulated_depreciation_account_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property float $purchase_value
 * @property float $salvage_value
 * @property int $useful_life_years
 * @property string $depreciation_method
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $accumulatedDepreciationAccount
 * @property-read \App\Models\Account $assetAccount
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepreciationEntry> $depreciationEntries
 * @property-read int|null $depreciation_entries_count
 * @property-read \App\Models\Account $depreciationExpenseAccount
 * @method static \Database\Factories\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAccumulatedDepreciationAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAssetAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDepreciationExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDepreciationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset wherePurchaseValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSalvageValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUsefulLifeYears($value)
 * @mixin \Eloquent
 */
class Asset extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Fixed assets are critical for financial reporting and operational tracking.
     *
     * @var string
     */
    protected $table = 'assets';

    /**
     * The attributes that are mass assignable.
     * These fields are essential for defining an asset's core characteristics.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'purchase_date',
        'purchase_value',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'asset_account_id',
        'depreciation_expense_account_id',
        'accumulated_depreciation_account_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances for consistent date manipulation.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'purchase_value' => MoneyCast::class,
        'salvage_value' => MoneyCast::class,
        'useful_life_years' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | Assets are intricately linked to other core accounting entities,
    | specifically the company they belong to, the relevant general ledger
    | accounts, and their associated depreciation schedules.
    */

    /**
     * Get the company that owns this asset.
     * An asset is always associated with a specific company in a multi-company setup. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the general ledger account (balance sheet) for this asset.
     * This links the asset to its representation on the company's balance sheet. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    /**
     * Get the depreciation expense account (profit & loss) for this asset.
     * This account records the periodic expense of the asset's wear and tear. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    /**
     * Get the accumulated depreciation account (contra-asset) for this asset.
     * This account accumulates the total depreciation charged against the asset over its life. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    /**
     * Get the depreciation entries associated with this asset.
     * Each asset generates multiple depreciation entries over its useful life. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }
}
