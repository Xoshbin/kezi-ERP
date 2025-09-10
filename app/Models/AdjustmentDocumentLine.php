<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\DocumentCurrencyMoneyCast;
use App\Observers\AdjustmentDocumentLineObserver;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \App\Models\AdjustmentDocument $adjustmentDocument
 */
#[ObservedBy([AdjustmentDocumentLineObserver::class])]
class AdjustmentDocumentLine extends Model
{
    /** @use HasFactory<\Database\Factories\AdjustmentDocumentLineFactory> */
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
        'unit_price' => DocumentCurrencyMoneyCast::class,
        'unit_price_company_currency' => BaseCurrencyMoneyCast::class,
        'subtotal' => DocumentCurrencyMoneyCast::class,
        'subtotal_company_currency' => BaseCurrencyMoneyCast::class,
        'total_line_tax' => DocumentCurrencyMoneyCast::class,
        'total_line_tax_company_currency' => BaseCurrencyMoneyCast::class,
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
        /** @var \App\Models\Currency $currency */
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
}
