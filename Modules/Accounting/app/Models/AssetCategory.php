<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Enums\Assets\DepreciationMethod;

class AssetCategory extends Model
{
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
