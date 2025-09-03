<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Enums\Assets\AssetStatus;
use App\Enums\Assets\DepreciationMethod;
use App\Observers\AssetObserver;
use Brick\Money\Money;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class Asset
 *
 * @property int $id
 * @property int $company_id
 * @property int $asset_account_id
 * @property int $depreciation_expense_account_id
 * @property int $accumulated_depreciation_account_id
 * @property string $name
 * @property Carbon $purchase_date
 * @property Money $purchase_value
 * @property Money $salvage_value
 * @property int $useful_life_years
 * @property string $depreciation_method
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $accumulatedDepreciationAccount
 * @property-read Account $assetAccount
 * @property-read Company $company
 * @property-read Collection<int, DepreciationEntry> $depreciationEntries
 * @property-read int|null $depreciation_entries_count
 * @property-read Account $depreciationExpenseAccount
 *
 * @method static AssetFactory factory($count = null, $state = [])
 * @method static Builder<static>|Asset newModelQuery()
 * @method static Builder<static>|Asset newQuery()
 * @method static Builder<static>|Asset query()
 * @method static Builder<static>|Asset whereAccumulatedDepreciationAccountId($value)
 * @method static Builder<static>|Asset whereAssetAccountId($value)
 * @method static Builder<static>|Asset whereCompanyId($value)
 * @method static Builder<static>|Asset whereCreatedAt($value)
 * @method static Builder<static>|Asset whereDepreciationExpenseAccountId($value)
 * @method static Builder<static>|Asset whereDepreciationMethod($value)
 * @method static Builder<static>|Asset whereId($value)
 * @method static Builder<static>|Asset whereName($value)
 * @method static Builder<static>|Asset wherePurchaseDate($value)
 * @method static Builder<static>|Asset wherePurchaseValue($value)
 * @method static Builder<static>|Asset whereSalvageValue($value)
 * @method static Builder<static>|Asset whereStatus($value)
 * @method static Builder<static>|Asset whereUpdatedAt($value)
 * @method static Builder<static>|Asset whereUsefulLifeYears($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AssetObserver::class])]
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
        'currency_id',
        'source_type',
        'source_id',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances for consistent date manipulation.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'purchase_value' => BaseCurrencyMoneyCast::class,
        'salvage_value' => BaseCurrencyMoneyCast::class,
        'useful_life_years' => 'integer',
        'status' => AssetStatus::class,
        'depreciation_method' => DepreciationMethod::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the company.
     * Without this, any retrieval of an `Asset` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var array
     */
    protected $with = ['company.currency'];

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
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the general ledger account (balance sheet) for this asset.
     * This links the asset to its representation on the company's balance sheet. [1]
     */
    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    /**
     * Get the depreciation expense account (profit & loss) for this asset.
     * This account records the periodic expense of the asset's wear and tear. [1]
     */
    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    /**
     * Get the accumulated depreciation account (contra-asset) for this asset.
     * This account accumulates the total depreciation charged against the asset over its life. [1]
     */
    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    /**
     * Get the depreciation entries associated with this asset.
     * Each asset generates multiple depreciation entries over its useful life. [1]
     */
    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the parent source model (e.g., VendorBill).
     * This relationship links the asset to its acquisition document.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all of the asset's journal entries.
     * An asset can have multiple journal entries (e.g., for acquisition, disposal).
     */
    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }
}
