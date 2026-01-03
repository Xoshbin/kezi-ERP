<?php

namespace Modules\Sales\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Modules\Foundation\Casts\DocumentCurrencyMoneyCast;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Product\Models\Product;
use Modules\Sales\Observers\QuoteLineObserver;

/**
 * Quote Line Model
 *
 * Represents a line item within a quote with pricing and discount information.
 *
 * @property int $id
 * @property int $quote_id
 * @property int|null $product_id
 * @property int|null $tax_id
 * @property int|null $income_account_id
 * @property string $description
 * @property numeric $quantity
 * @property string|null $unit
 * @property int $line_order
 * @property Money $unit_price
 * @property float $discount_percentage
 * @property Money $discount_amount
 * @property Money $subtotal
 * @property Money $tax_amount
 * @property Money $total
 * @property Money|null $unit_price_company_currency
 * @property Money|null $discount_amount_company_currency
 * @property Money|null $subtotal_company_currency
 * @property Money|null $tax_amount_company_currency
 * @property Money|null $total_company_currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Quote $quote
 * @property-read Product|null $product
 * @property-read Tax|null $tax
 * @property-read Account|null $incomeAccount
 */
#[ObservedBy([QuoteLineObserver::class, AuditLogObserver::class])]
class QuoteLine extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quote_lines';

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `quote` relationship is critical because the `DocumentCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the parent quote.
     *
     * @var list<string>
     */
    protected $with = ['quote.currency'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'quote_id',
        'product_id',
        'tax_id',
        'income_account_id',
        'description',
        'quantity',
        'unit',
        'line_order',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'total',
        'unit_price_company_currency',
        'discount_amount_company_currency',
        'subtotal_company_currency',
        'tax_amount_company_currency',
        'total_company_currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'line_order' => 'integer',
        'discount_percentage' => 'decimal:2',
        'unit_price' => DocumentCurrencyMoneyCast::class,
        'discount_amount' => DocumentCurrencyMoneyCast::class,
        'subtotal' => DocumentCurrencyMoneyCast::class,
        'tax_amount' => DocumentCurrencyMoneyCast::class,
        'total' => DocumentCurrencyMoneyCast::class,
        'unit_price_company_currency' => BaseCurrencyMoneyCast::class,
        'discount_amount_company_currency' => BaseCurrencyMoneyCast::class,
        'subtotal_company_currency' => BaseCurrencyMoneyCast::class,
        'tax_amount_company_currency' => BaseCurrencyMoneyCast::class,
        'total_company_currency' => BaseCurrencyMoneyCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the Quote that owns the QuoteLine.
     *
     * @return BelongsTo<Quote, static>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the Product associated with the QuoteLine.
     *
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the Tax applied to the QuoteLine.
     *
     * @return BelongsTo<Tax, static>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the Income Account associated with the QuoteLine.
     *
     * @return BelongsTo<Account, static>
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Sales\Database\Factories\QuoteLineFactory
    {
        return \Modules\Sales\Database\Factories\QuoteLineFactory::new();
    }
}
