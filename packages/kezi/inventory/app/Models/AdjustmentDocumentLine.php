<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Casts\DocumentCurrencyMoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Database\Factories\AdjustmentDocumentLineFactory;
use Kezi\Inventory\Observers\AdjustmentDocumentLineObserver;
use Kezi\Product\Models\Product;

/**
 * @property-read AdjustmentDocument $adjustmentDocument
 * @property int $id
 * @property int $company_id
 * @property int $adjustment_document_id
 * @property int|null $product_id
 * @property int|null $tax_id
 * @property int $account_id
 * @property int|null $currency_id
 * @property \Brick\Money\Money|null $unit_price_company_currency
 * @property \Brick\Money\Money|null $subtotal_company_currency
 * @property \Brick\Money\Money|null $total_line_tax_company_currency
 * @property string $description
 * @property numeric $quantity
 * @property \Brick\Money\Money $unit_price
 * @property \Brick\Money\Money $subtotal
 * @property \Brick\Money\Money $total_line_tax
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Account $account
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AdjustmentDocumentLine> $lines
 * @property-read int|null $lines_count
 * @property-read Product|null $product
 * @property-read Tax|null $tax
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereAdjustmentDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereSubtotalCompanyCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereTotalLineTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereTotalLineTaxCompanyCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereUnitPriceCompanyCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocumentLine whereUpdatedAt($value)
 * @method static \Kezi\Inventory\Database\Factories\AdjustmentDocumentLineFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AdjustmentDocumentLineObserver::class])]
class AdjustmentDocumentLine extends Model
{
    /** @use HasFactory<\Kezi\Inventory\Database\Factories\AdjustmentDocumentLineFactory> */
    use HasFactory;

    protected $table = 'adjustment_document_lines';

    protected $fillable = [
        'company_id', // Foreign key to the parent company, ensuring data integrity [2, 3].
        'adjustment_document_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'unit_price_company_currency',
        'tax_id',
        'subtotal',
        'subtotal_company_currency',
        'total_line_tax',
        'total_line_tax_company_currency',
        'account_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'unit_price_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'subtotal' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'subtotal_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'total_line_tax' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_line_tax_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `adjustmentDocument.currency` relationship is critical because the `DocumentCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the parent adjustment document.
     * Without this, any retrieval of an `AdjustmentDocumentLine` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['adjustmentDocument.currency'];

    protected static function booted(): void
    {
        static::saving(function (self $line) {
            $line->calculateLineTotals();
        });
    }

    public function calculateLineTotals(): void
    {
        /** @var Currency $currency */
        $currency = $this->adjustmentDocument->currency;
        $quantity = $this->quantity;

        // If unit_price is already a Money object, use it. Otherwise, create it from the numeric value.
        $unitPrice = $this->unit_price instanceof Money
            ? $this->unit_price
            : Money::of($this->unit_price ?? 0, $currency->code);

        $subtotal = $unitPrice->multipliedBy($quantity, RoundingMode::HALF_UP);
        $this->subtotal = $subtotal;

        $totalLineTax = Money::of(0, $currency->code);
        if ($this->tax_id) {
            $tax = Tax::find($this->tax_id);
            if ($tax) {
                // NOTE: The rate in Tax model is a float (e.g., 0.10 for 10%)
                $totalLineTax = $subtotal->multipliedBy($tax->rate, RoundingMode::HALF_UP);
            }
        }
        $this->total_line_tax = $totalLineTax;
    }

    /**
     * Get the company that this rate belongs to.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<AdjustmentDocument, static>
     */
    public function adjustmentDocument(): BelongsTo
    {
        return $this->belongsTo(AdjustmentDocument::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Tax, static>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the line items for this adjustment document.
     * An adjustment document consists of multiple detail lines.
     */
    /**
     * @return HasMany<AdjustmentDocumentLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(AdjustmentDocumentLine::class);
    }

    protected static function newFactory(): AdjustmentDocumentLineFactory
    {
        return AdjustmentDocumentLineFactory::new();
    }
}
