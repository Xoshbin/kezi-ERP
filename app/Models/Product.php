<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Observers\ProductObserver;
use Brick\Money\Money;
use Database\Factories\ProductFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $income_account_id
 * @property int|null $expense_account_id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property Money|null $unit_price
 * @property ProductType $type
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Account|null $expenseAccount
 * @property-read Account|null $incomeAccount
 * @property-read Account|null $inventoryAccount
 * @property-read Account|null $defaultCogsAccount
 * @property-read Account|null $stockInputAccount
 *
 * @method static Builder<static>|Product active()
 * @method static Builder<static>|Product bySku($sku, $companyId)
 * @method static ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product onlyTrashed()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product whereCompanyId($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDeletedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereExpenseAccountId($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereIncomeAccountId($value)
 * @method static Builder<static>|Product whereIsActive($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereSku($value)
 * @method static Builder<static>|Product whereType($value)
 * @method static Builder<static>|Product whereUnitPrice($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 * @method static Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Product withoutTrashed()
 *
 * @mixin Eloquent
 */
#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name', 'description'];

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

    protected $casts = [
        'unit_price' => BaseCurrencyMoneyCast::class,
        'average_cost' => BaseCurrencyMoneyCast::class,
        'is_active' => 'boolean',
        'inventory_valuation_method' => ValuationMethod::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => ProductType::class,
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the company.
     * Without this, any retrieval of a `Product` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['company.currency'];

    /**
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['sku'];
    }

    /**
     * Get the Company that owns the Product.
     * This relationship is fundamental in a multi-company accounting setup, ensuring products are scoped to specific entities.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Account (from the Chart of Accounts) that is the default income account for this product.
     * This is crucial for automating revenue recognition when the product is sold, impacting the Income Statement.
     */
    /**
     * @return BelongsTo<Account, static>
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    /**
     * Get the Account (from the Chart of Accounts) that is the default expense account for this product.
     * This enables automated cost allocation and impacts the Expense section of the Income Statement, aligning with the double-entry principle.
     */
    /**
     * @return BelongsTo<Account, static>
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    // ADDED: Relationship to the default inventory/valuation account.
    /**
     * @return BelongsTo<Account, static>
     */
    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_inventory_account_id');
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function defaultCogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_cogs_account_id');
    }

    // ADDED: Relationship to the default stock input/accrual account.
    /**
     * @return BelongsTo<Account, static>
     */
    public function stockInputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_stock_input_account_id');
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function defaultPriceDifferenceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_price_difference_account_id');
    }

    /**
     * Scope a query to only include active products.
     * This is a common query scope to filter out inactive items in various application contexts.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to find a product by its SKU within a specific company.
     * SKU should be unique per company to prevent data duplication and maintain accurate inventory records.
     *
     * @param  Builder  $query
     * @param  string  $sku
     * @param  int  $companyId
     * @return Builder
     */
    public function scopeBySku($query, $sku, $companyId)
    {
        return $query->where('sku', $sku)->where('company_id', $companyId);
    }

    /**
     * @return HasMany<InvoiceLine, static>
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * @return HasMany<VendorBillLine, static>
     */
    public function vendorBillLines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class);
    }

    /**
     * @return HasMany<StockMove, static>
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class);
    }

    /**
     * @return HasMany<InventoryCostLayer, static>
     */
    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }
}
