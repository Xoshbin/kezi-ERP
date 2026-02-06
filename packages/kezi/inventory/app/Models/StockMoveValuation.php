<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;
use Kezi\Inventory\Enums\Inventory\CostSource;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $stock_move_id
 * @property numeric $quantity
 * @property \Brick\Money\Money $cost_impact
 * @property ValuationMethod $valuation_method
 * @property string $move_type
 * @property int|null $journal_entry_id
 * @property string $source_type
 * @property int $source_id
 * @property CostSource|null $cost_source Source of cost determination: vendor_bill, average_cost, cost_layer, unit_price, manual, company_default
 * @property string|null $cost_source_reference Additional context about cost source (e.g., VendorBill:123, CostLayer:456)
 * @property array<array-key, mixed>|null $cost_warnings Warnings generated during cost determination
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read JournalEntry|null $journalEntry
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Model $source
 * @property-read \Kezi\Inventory\Models\StockMove $stockMove
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereCostImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereCostSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereCostSourceReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereCostWarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereMoveType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereStockMoveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMoveValuation whereValuationMethod($value)
 * @method static \Kezi\Inventory\Database\Factories\StockMoveValuationFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class StockMoveValuation extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\StockMoveValuationFactory
    {
        return \Kezi\Inventory\Database\Factories\StockMoveValuationFactory::new();
    }

    protected $fillable = [
        'company_id',
        'product_id',
        'stock_move_id',
        'quantity',
        'cost_impact',
        'valuation_method',
        'move_type',
        'journal_entry_id',
        'source_type',
        'source_id',
        'cost_source',
        'cost_source_reference',
        'cost_warnings',
    ];

    protected $casts = [
        'cost_impact' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'valuation_method' => ValuationMethod::class,
        'cost_source' => CostSource::class,
        'cost_warnings' => 'array',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the company.
     * Without this, any retrieval of a `StockMoveValuation` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['company.currency'];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockMove, static>
     */
    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }

    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return MorphTo<Model, static>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
