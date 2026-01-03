<?php

namespace Modules\Product\Models;

use App\Models\Company;
use Brick\Money\Money;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\ReorderingRule;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockQuant;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Sales\Models\InvoiceLine;
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
 * @property \Modules\Product\Enums\Products\ProductType $type
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
 * @method static \Modules\Product\Database\Factories\ProductFactory factory($count = null, $state = [])
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
#[ObservedBy([\Modules\Product\Observers\ProductObserver::class])]
class Product extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected static function newFactory(): \Modules\Product\Database\Factories\ProductFactory
    {
        return \Modules\Product\Database\Factories\ProductFactory::new();
    }

    /** @var array<int, string> */
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
        'lot_tracking_enabled',
    ];

    protected $casts = [
        'unit_price' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'average_cost' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'is_active' => 'boolean',
        'inventory_valuation_method' => ValuationMethod::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => \Modules\Product\Enums\Products\ProductType::class,
        'lot_tracking_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->type === \Modules\Product\Enums\Products\ProductType::Storable && empty($product->default_inventory_account_id)) {
                throw ValidationException::withMessages([
                    'default_inventory_account_id' => __('validation.required', ['attribute' => __('product::product.default_inventory_account')]),
                ]);
            }

            // Phase 1: Standard costing is not supported
            if ($product->inventory_valuation_method === ValuationMethod::STANDARD) {
                throw ValidationException::withMessages([
                    'inventory_valuation_method' => __('This project does not support Standard costing in Phase 1.'),
                ]);
            }
        });
    }

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
     * @return HasMany<StockMoveProductLine, static>
     */
    public function stockMoveProductLines(): HasMany
    {
        return $this->hasMany(StockMoveProductLine::class);
    }

    /**
     * Get stock moves that contain this product through product lines
     *
     * @return HasManyThrough<StockMove, StockMoveProductLine, static>
     */
    public function stockMoves(): HasManyThrough
    {
        return $this->hasManyThrough(StockMove::class, StockMoveProductLine::class);
    }

    /**
     * @return HasMany<InventoryCostLayer, static>
     */
    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }

    /**
     * @return HasMany<ReorderingRule, static>
     */
    public function reorderingRules(): HasMany
    {
        return $this->hasMany(ReorderingRule::class);
    }

    /**
     * Get all stock quants for this product across all locations.
     *
     * @return HasMany<StockQuant, static>
     */
    public function stockQuants(): HasMany
    {
        return $this->hasMany(StockQuant::class);
    }

    /**
     * Get total quantity on hand across all locations from StockQuant.
     * This is the single source of truth for inventory quantities.
     */
    public function getQuantityOnHandAttribute(): float
    {
        return (float) $this->stockQuants()->sum('quantity');
    }

    /**
     * Get available quantity (total - reserved) across all locations.
     */
    public function getAvailableQuantityAttribute(): float
    {
        $total = $this->stockQuants()->sum('quantity');
        $reserved = $this->stockQuants()->sum('reserved_quantity');

        return (float) ($total - $reserved);
    }

    /**
     * Get quantity on hand for a specific location.
     */
    public function getQuantityAtLocation(int $locationId): float
    {
        return (float) $this->stockQuants()
            ->where('location_id', $locationId)
            ->sum('quantity');
    }

    /**
     * Get available quantity at a specific location.
     */
    public function getAvailableQuantityAtLocation(int $locationId): float
    {
        $quant = $this->stockQuants()
            ->where('location_id', $locationId)
            ->first();

        if (! $quant) {
            return 0.0;
        }

        return $quant->quantity - $quant->reserved_quantity;
    }
}
