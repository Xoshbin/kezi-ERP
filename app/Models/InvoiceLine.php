<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\MoneyCast;
use App\Observers\InvoiceLineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $product_id
 * @property int|null $tax_id
 * @property int $income_account_id
 * @property string $description
 * @property numeric $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property float $total_line_tax
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AnalyticAccount|null $analyticAccount
 * @property-read Account $incomeAccount
 * @property-read Invoice $invoice
 * @property-read Product|null $product
 * @property-read Tax|null $tax
 * @method static InvoiceLineFactory factory($count = null, $state = [])
 * @method static Builder<static>|InvoiceLine newModelQuery()
 * @method static Builder<static>|InvoiceLine newQuery()
 * @method static Builder<static>|InvoiceLine query()
 * @method static Builder<static>|InvoiceLine whereCreatedAt($value)
 * @method static Builder<static>|InvoiceLine whereDescription($value)
 * @method static Builder<static>|InvoiceLine whereId($value)
 * @method static Builder<static>|InvoiceLine whereIncomeAccountId($value)
 * @method static Builder<static>|InvoiceLine whereInvoiceId($value)
 * @method static Builder<static>|InvoiceLine whereProductId($value)
 * @method static Builder<static>|InvoiceLine whereQuantity($value)
 * @method static Builder<static>|InvoiceLine whereSubtotal($value)
 * @method static Builder<static>|InvoiceLine whereTaxId($value)
 * @method static Builder<static>|InvoiceLine whereTotalLineTax($value)
 * @method static Builder<static>|InvoiceLine whereUnitPrice($value)
 * @method static Builder<static>|InvoiceLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ObservedBy([InvoiceLineObserver::class])]
class InvoiceLine extends Model
{
    // Leveraging Laravel's HasFactory trait for simplified model factory creation in testing/seeding [5, 6].
    use HasFactory;

    /**
     * The table associated with the model.
     * Eloquent convention would assume 'invoice_lines' automatically [7], but explicit definition is good practice.
     *
     * @var string
     */
    protected $table = 'invoice_lines';

    /**
     * The attributes that are mass assignable.
     * This array specifies which attributes can be filled via mass assignment,
     * protecting against the mass assignment vulnerability [8-10].
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',          // Foreign key to the parent company [3, 4]
        'invoice_id',          // Foreign key to the parent invoice [3, 4]
        'product_id',          // Foreign key to the product (nullable) [3, 4]
        'description',         // Text description for the line item [3, 4]
        'quantity',            // Quantity of the product/service [3, 4]
        'unit_price',          // Price per unit [3, 4]
        'unit_price_company_currency', // Price per unit in company currency
        'tax_id',              // Foreign key to the tax applied (nullable) [3, 4]
        'subtotal',            // Calculated subtotal for the line (quantity * unit_price) [3, 4]
        'subtotal_company_currency',   // Subtotal in company currency
        'total_line_tax',      // Total tax amount for this line [3, 4]
        'total_line_tax_company_currency', // Total tax in company currency
        'income_account_id',   // Foreign key to the specific income account [3, 4]
    ];

    /**
     * The attributes that should be cast.
     * This defines how database columns are converted to PHP data types when accessed [11, 12].
     * Decimal casting ensures numeric precision matching database schema (e.g., decimal(15, 2) in migration) [4].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => MoneyCast::class,
        'unit_price_company_currency' => MoneyCast::class,
        'subtotal' => MoneyCast::class,
        'subtotal_company_currency' => MoneyCast::class,
        'total_line_tax' => MoneyCast::class,
        'total_line_tax_company_currency' => MoneyCast::class,
        'created_at' => 'datetime', // Eloquent automatically manages these, but explicit casting is robust [12, 13].
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that this rate belongs to.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the **Invoice** that owns the InvoiceLine.
     * Defines a one-to-many (inverse) or "belongs to" relationship [14, 15].
     * This connects an invoice line back to its header invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the **Product** associated with the InvoiceLine.
     * Defines a "belongs to" relationship for the product linked to this line item [14, 15].
     * The product_id is nullable in the schema [4], allowing for descriptive lines without a specific product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the **Tax** applied to the InvoiceLine.
     * Defines a "belongs to" relationship for the tax [14, 15].
     * The tax_id is nullable in the schema [4].
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the **Income Account** associated with the InvoiceLine.
     * Defines a "belongs to" relationship to the Chart of Accounts for the specific income recognition [14, 15].
     * The foreign key is explicitly provided as 'income_account_id' because it deviates from Eloquent's default convention
     * (which would assume 'account_id' if the method name were just 'account') [16].
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }

    // Since financial records like InvoiceLines, once part of a posted Invoice,
    // should not be directly deleted or altered [1-3],
    // soft deletes are generally not applied to such core financial transaction components.
    // The immutability and correction mechanisms (contra-entries) are handled at the parent Invoice level [1-3].
    // The migration's `cascadeOnDelete()` for `invoice_id` ensures that if a draft invoice is deleted, its lines follow [4].

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * This robust implementation prevents N+1 query issues.
     */
    public function getCurrencyIdAttribute(): int
    {
        // If the relationship is already loaded, use it. Otherwise, use the foreign key.
        return $this->invoice->currency_id ?? $this->invoice()->getForeignKeyResults()->first()->currency_id;
    }
}
