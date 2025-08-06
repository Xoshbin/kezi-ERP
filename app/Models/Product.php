<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\ProductObserver;
use App\Enums\Products\ProductType;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Inventory\ValuationMethod;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * @property int $id
 * @property int $company_id
 * @property int $income_account_id
 * @property int $expense_account_id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property float $unit_price
 * @property string $type
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Account $expenseAccount
 * @property-read \App\Models\Account $incomeAccount
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product bySku($sku, $companyId)
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIncomeAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products'; // Laravel's convention is 'products', so explicit declaration is optional but good for clarity.

    /**
     * The attributes that are mass assignable.
     * These fields are typically populated via user input or automated processes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'sku',
        'description',
        'unit_price',
        'type',
        'income_account_id',
        'expense_account_id',
        'is_active',
        'inventory_valuation_method',
        'default_inventory_account_id',
        'default_cogs_account_id',
        'default_stock_input_account_id',
        'default_price_difference_account_id',
        'average_cost',
    ];

    /**
     * The attributes that should be cast.
     * This ensures proper data types are used when interacting with the model attributes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => MoneyCast::class,
        'average_cost' => MoneyCast::class,
        'is_active' => 'boolean',
        'inventory_valuation_method' => ValuationMethod::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => ProductType::class,
    ];

    /**
     * Get the Company that owns the Product.
     * This relationship is fundamental in a multi-company accounting setup, ensuring products are scoped to specific entities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Account (from the Chart of Accounts) that is the default income account for this product.
     * This is crucial for automating revenue recognition when the product is sold, impacting the Income Statement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function incomeAccount()
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    /**
     * Get the Account (from the Chart of Accounts) that is the default expense account for this product.
     * This enables automated cost allocation and impacts the Expense section of the Income Statement, aligning with the double-entry principle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function defaultInventoryAccount()
    {
        return $this->belongsTo(Account::class, 'default_inventory_account_id');
    }

    public function defaultCogsAccount()
    {
        return $this->belongsTo(Account::class, 'default_cogs_account_id');
    }

    public function defaultStockInputAccount()
    {
        return $this->belongsTo(Account::class, 'default_stock_input_account_id');
    }

    public function defaultPriceDifferenceAccount()
    {
        return $this->belongsTo(Account::class, 'default_price_difference_account_id');
    }

    /**
     * Scope a query to only include active products.
     * This is a common query scope to filter out inactive items in various application contexts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to find a product by its SKU within a specific company.
     * SKU should be unique per company to prevent data duplication and maintain accurate inventory records.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sku
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySku($query, $sku, $companyId)
    {
        return $query->where('sku', $sku)->where('company_id', $companyId);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function vendorBillLines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class);
    }

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * This robust implementation prevents N+1 query issues.
     */
    public function getCurrencyIdAttribute(): int
    {
        // If the company relationship is loaded, use it. If not, lazy-load it.
        return $this->company->currency_id ?? $this->company()->first()->currency_id;
    }
}
