<?php

namespace App\Models;

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
 *
 * @property int $id Primary key, auto-incrementing.
 * @property int $company_id Foreign key to the Company this asset belongs to. [1]
 * @property string $name The name or description of the asset (e.g., 'Office Building', 'Production Machine'). [1]
 * @property \Illuminate\Support\Carbon $purchase_date The date the asset was acquired. [1]
 * @property float $purchase_value The original cost or value of the asset at acquisition. [1]
 * @property float $salvage_value The estimated residual value of the asset at the end of its useful life. [1]
 * @property int $useful_life_years The estimated useful life of the asset in years. [1]
 * @property string $depreciation_method The method used for calculating depreciation (e.g., 'Straight-line'). [1]
 * @property int $asset_account_id Foreign key to the general ledger account representing the asset on the balance sheet. [1]
 * @property int $depreciation_expense_account_id Foreign key to the expense account for recording depreciation in the P&L. [1]
 * @property int $accumulated_depreciation_account_id Foreign key to the contra-asset account for accumulated depreciation. [1]
 * @property string $status The current status of the asset (e.g., 'Draft', 'Confirmed', 'Depreciating', 'Fully Depreciated', 'Sold'). [1]
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created. [1]
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated. [1]
 *
 * @property-read \App\Models\Company $company The company that owns this asset.
 * @property-read \App\Models\Account $assetAccount The balance sheet account associated with this asset.
 * @property-read \App\Models\Account $depreciationExpenseAccount The profit & loss expense account for this asset's depreciation.
 * @property-read \App\Models\Account $accumulatedDepreciationAccount The contra-asset account for this asset's accumulated depreciation.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DepreciationEntry[] $depreciationEntries The depreciation entries recorded for this asset.
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
        'purchase_value' => 'float',
        'salvage_value' => 'float',
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
