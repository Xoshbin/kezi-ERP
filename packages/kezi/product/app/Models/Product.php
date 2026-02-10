<?php

namespace Kezi\Product\Models;

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
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\ReorderingRule;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Purchase\Models\VendorBillLine;
use Kezi\Sales\Models\InvoiceLine;
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
 * @property \Kezi\Product\Enums\Products\ProductType $type
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $default_inventory_account_id
 * @property int|null $default_cogs_account_id
 * @property int|null $default_stock_input_account_id
 * @property \Kezi\Inventory\Enums\Inventory\ValuationMethod $inventory_valuation_method
 * @property \Brick\Money\Money|null $average_cost
 * @property bool $is_template
 * @property bool $has_price_override
 * @property int|null $parent_product_id
 * @property string|null $variant_sku_suffix
 * @property \Kezi\Inventory\Enums\Inventory\TrackingType $tracking_type
 * @property float $weight
 * @property float $volume
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Accounting\Models\Tax> $purchaseTaxes
 * @property array<int, mixed>|null $product_attributes
 * @property array<int, mixed>|null $productAttributes
 * @property-read Company $company
 * @property-read Account|null $expenseAccount
 * @property-read Account|null $incomeAccount
 * @property-read Account|null $inventoryAccount
 * @property-read Account|null $defaultCogsAccount
 * @property-read Account|null $stockInputAccount
 *
 * @method static Builder<static>|Product active()
 * @method static Builder<static>|Product bySku($sku, $companyId)
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
 * @property int|null $deferred_revenue_account_id
 * @property int|null $deferred_expense_account_id
 * @property int|null $default_price_difference_account_id
 * @property float $quantity_on_hand
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Manufacturing\Models\BillOfMaterial> $billsOfMaterials
 * @property-read int|null $bills_of_materials_count
 * @property-read Account|null $defaultPriceDifferenceAccount
 * @property-read Account|null $deferredExpenseAccount
 * @property-read Account|null $deferredRevenueAccount
 * @property-read float $available_quantity
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InventoryCostLayer> $inventoryCostLayers
 * @property-read int|null $inventory_cost_layers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceLine> $invoiceLines
 * @property-read int|null $invoice_lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Manufacturing\Models\ManufacturingOrder> $manufacturingOrders
 * @property-read int|null $manufacturing_orders_count
 * @property-read Product|null $parent
 * @property-read int|null $purchase_taxes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReorderingRule> $reorderingRules
 * @property-read int|null $reordering_rules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMoveProductLine> $stockMoveProductLines
 * @property-read int|null $stock_move_product_lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMove> $stockMoves
 * @property-read int|null $stock_moves_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockQuant> $stockQuants
 * @property-read int|null $stock_quants_count
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Product\Models\ProductVariantAttribute> $variantAttributes
 * @property-read int|null $variant_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $variants
 * @property-read int|null $variants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VendorBillLine> $vendorBillLines
 * @property-read int|null $vendor_bill_lines_count
 *
 * @method static Builder<static>|Product whereAverageCost($value)
 * @method static Builder<static>|Product whereDefaultCogsAccountId($value)
 * @method static Builder<static>|Product whereDefaultInventoryAccountId($value)
 * @method static Builder<static>|Product whereDefaultPriceDifferenceAccountId($value)
 * @method static Builder<static>|Product whereDefaultStockInputAccountId($value)
 * @method static Builder<static>|Product whereDeferredExpenseAccountId($value)
 * @method static Builder<static>|Product whereDeferredRevenueAccountId($value)
 * @method static Builder<static>|Product whereHasPriceOverride($value)
 * @method static Builder<static>|Product whereInventoryValuationMethod($value)
 * @method static Builder<static>|Product whereIsTemplate($value)
 * @method static Builder<static>|Product whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Product whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Product whereLocale(string $column, string $locale)
 * @method static Builder<static>|Product whereLocales(string $column, array $locales)
 * @method static Builder<static>|Product whereParentProductId($value)
 * @method static Builder<static>|Product whereProductAttributes($value)
 * @method static Builder<static>|Product whereQuantityOnHand($value)
 * @method static Builder<static>|Product whereTrackingType($value)
 * @method static Builder<static>|Product whereVariantSkuSuffix($value)
 * @method static Builder<static>|Product whereVolume($value)
 * @method static Builder<static>|Product whereWeight($value)
 * @method static \Kezi\Product\Database\Factories\ProductFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
#[ObservedBy([\Kezi\Product\Observers\ProductObserver::class])]
class Product extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected static function newFactory(): \Kezi\Product\Database\Factories\ProductFactory
    {
        return \Kezi\Product\Database\Factories\ProductFactory::new();
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
        'tracking_type',
        'deferred_revenue_account_id',
        'deferred_expense_account_id',
        'weight',
        'volume',
        'is_template',
        'parent_product_id',
        'variant_sku_suffix',
        'has_price_override',
        'product_attributes',
    ];

    protected $casts = [
        'unit_price' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'average_cost' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'is_active' => 'boolean',
        'inventory_valuation_method' => ValuationMethod::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => \Kezi\Product\Enums\Products\ProductType::class,
        'tracking_type' => \Kezi\Inventory\Enums\Inventory\TrackingType::class,
        'weight' => 'float',
        'volume' => 'float',
        'is_template' => 'boolean',
        'has_price_override' => 'boolean',
        'product_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->type === \Kezi\Product\Enums\Products\ProductType::Storable && empty($product->default_inventory_account_id)) {
                throw ValidationException::withMessages([
                    'default_inventory_account_id' => __('validation.required', ['attribute' => __('product::product.default_inventory_account')]),
                ]);
            }

            // Phase 1: Standard costing is not supported
            if ($product->inventory_valuation_method === ValuationMethod::Standard) {
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

    /**
     * @return BelongsTo<Account, static>
     */
    public function deferredRevenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deferred_revenue_account_id');
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function deferredExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deferred_expense_account_id');
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

    /**
     * Check if product has any stock moves (for tracking type immutability)
     */
    public function hasStockMoves(): bool
    {
        return $this->stockMoveProductLines()->exists();
    }

    /**
     * Get bills of materials where this product is the finished product
     *
     * @return HasMany<\Kezi\Manufacturing\Models\BillOfMaterial, static>
     */
    public function billsOfMaterials(): HasMany
    {
        return $this->hasMany(\Kezi\Manufacturing\Models\BillOfMaterial::class);
    }

    /**
     * Get manufacturing orders for this product
     *
     * @return HasMany<\Kezi\Manufacturing\Models\ManufacturingOrder, static>
     */
    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(\Kezi\Manufacturing\Models\ManufacturingOrder::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    /**
     * @return HasMany<Product, static>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_product_id');
    }

    /**
     * @return HasMany<ProductVariantAttribute, static>
     */
    public function variantAttributes(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class, 'product_id');
    }

    public function isTemplate(): bool
    {
        return $this->is_template;
    }

    public function isVariant(): bool
    {
        return ! empty($this->parent_product_id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\Kezi\Accounting\Models\Tax, static>
     */
    public function purchaseTaxes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Kezi\Accounting\Models\Tax::class, 'product_purchase_tax');
    }
}
