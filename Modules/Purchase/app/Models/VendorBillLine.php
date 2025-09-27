<?php

namespace Modules\Purchase\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\DocumentCurrencyMoneyCast;
use App\Observers\VendorBillLineObserver;
use Brick\Money\Money;
use Database\Factories\VendorBillLineFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

// As a fundamental principle of accounting, financial line items,
// much like their parent documents (Vendor Bills), are part of the immutable
// financial record once the bill is "Posted" [1-3].
// Therefore, the SoftDeletes trait is **intentionally omitted** to ensure
// the integrity and auditability of historical accounting data [3].
// Any corrections to posted lines must be handled via new, offsetting entries
// (e.g., adjustment documents like credit notes or new journal entries) [3].
/**
 * @property int $id
 * @property int $vendor_bill_id
 * @property int|null $product_id
 * @property int|null $tax_id
 * @property int $expense_account_id
 * @property int|null $analytic_account_id
 * @property string $description
 * @property numeric $quantity
 * @property Money $unit_price
 * @property Money $subtotal
 * @property Money $total_line_tax
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AnalyticAccount|null $analyticAccount
 * @property-read Account $expenseAccount
 * @property-read Product|null $product
 * @property-read Tax|null $tax
 * @property-read VendorBill $vendorBill
 *
 * @method static VendorBillLineFactory factory($count = null, $state = [])
 * @method static Builder<static>|VendorBillLine newModelQuery()
 * @method static Builder<static>|VendorBillLine newQuery()
 * @method static Builder<static>|VendorBillLine query()
 * @method static Builder<static>|VendorBillLine whereAnalyticAccountId($value)
 * @method static Builder<static>|VendorBillLine whereCreatedAt($value)
 * @method static Builder<static>|VendorBillLine whereDescription($value)
 * @method static Builder<static>|VendorBillLine whereExpenseAccountId($value)
 * @method static Builder<static>|VendorBillLine whereId($value)
 * @method static Builder<static>|VendorBillLine whereProductId($value)
 * @method static Builder<static>|VendorBillLine whereQuantity($value)
 * @method static Builder<static>|VendorBillLine whereSubtotal($value)
 * @method static Builder<static>|VendorBillLine whereTaxId($value)
 * @method static Builder<static>|VendorBillLine whereTotalLineTax($value)
 * @method static Builder<static>|VendorBillLine whereUnitPrice($value)
 * @method static Builder<static>|VendorBillLine whereUpdatedAt($value)
 * @method static Builder<static>|VendorBillLine whereVendorBillId($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy([VendorBillLineObserver::class])]
class VendorBillLine extends Model
{
    /** @use HasFactory<\Database\Factories\VendorBillLineFactory> */
    use HasFactory;

    /**
     * The database table associated with the model.
     * Explicitly defining the table name for clarity and consistency with migration schemas [2, 4].
     *
     * @var string
     */
    protected $table = 'vendor_bill_lines'; // Matches table name from source [2] section 13.

    /**
     * The attributes that are mass assignable.
     * These fields can be safely filled via mass assignment, ensuring
     * that only expected data is set on creation or update, a crucial security feature [5, 6].
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',             // Foreign key to the parent company, ensuring data integrity [2, 3].
        'vendor_bill_id',          // Foreign key to the parent VendorBill, linking each line to its primary document [2].
        'product_id',              // Nullable foreign key to the Product model, identifying the item purchased [2].
        'description',             // A detailed textual description of the line item [2].
        'quantity',                // The quantity of the product or service on this line [2].
        'unit_price',              // The price per unit of the item [2].
        'unit_price_company_currency', // Price per unit in company currency
        'tax_id',                  // Nullable foreign key to the Tax model, representing the tax applied to this line [2].
        'subtotal',                // The calculated subtotal for this line, typically quantity * unit_price [2].
        'subtotal_company_currency',   // Subtotal in company currency
        'total_line_tax',          // The total tax amount specifically for this line item [2].
        'total_line_tax_company_currency', // Total tax in company currency
        'expense_account_id',      // Foreign key to the Account model, for proper expense classification in the Chart of Accounts [2].
        'asset_category_id',       // Optional link to asset category to create an asset from this line.
        'analytic_account_id',     // Nullable foreign key to the AnalyticAccount model for management/cost accounting [2, 7].
        // implies its applicability at the document line level for richer analytic tracking.
    ];

    /**
     * The attributes that should be cast to native types.
     * Essential for maintaining numerical precision for financial values (`decimal:2`)
     * and ensuring proper date handling for timestamps [2, 9].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2', // Ensures precision for quantities, allowing for fractional units.
        'unit_price' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class, // Document currency amounts
        'unit_price_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class, // Company base currency amounts
        'subtotal' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class, // Document currency amounts
        'subtotal_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class, // Company base currency amounts
        'total_line_tax' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class, // Document currency amounts
        'total_line_tax_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class, // Company base currency amounts
        'created_at' => 'datetime',  // Automatically managed by Eloquent for audit trails [2].
        'updated_at' => 'datetime',  // Automatically managed by Eloquent [2].
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `vendorBill` relationship is critical because the `MoneyCast`
     * for monetary fields on this model depends on the currency context provided by the parent bill.
     * Without this, any retrieval of a `VendorBillLine` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['vendorBill'];

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
     * Get the Vendor Bill that owns the Vendor Bill Line.
     * Establishes a **BelongsTo** relationship with the `VendorBill` model,
     * linking each line item directly to its originating vendor bill document [2, 10-12].
     */
    /**
     * @return BelongsTo<VendorBill, static>
     */
    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class, 'vendor_bill_id');
    }

    /**
     * Get the Product associated with the Vendor Bill Line.
     * Defines a **BelongsTo** relationship to the `Product` model [10-12].
     * This relationship is nullable, acknowledging that some bill lines may simply be descriptive
     * without linking to a specific product from the catalog [2].
     */
    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the Tax applied to the Vendor Bill Line.
     * Establishes a **BelongsTo** relationship with the `Tax` model [10-12].
     * This is crucial for correct tax calculation and reporting, and is nullable for tax-exempt items [2].
     */
    /**
     * @return BelongsTo<Tax, static>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * Get the Expense Account associated with the Vendor Bill Line.
     * This **BelongsTo** relationship is fundamental for the double-entry accounting system,
     * directing the cost of each line item to the appropriate expense account in the Chart of Accounts [2, 10-12].
     */
    /**
     * @return BelongsTo<Account, static>
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    /**
     * Get the Analytic Account for the Vendor Bill Line.
     * Defines a **BelongsTo** relationship to the `AnalyticAccount` model [10-12].
     * This provides a granular layer for internal management accounting, allowing costs
     * to be tracked against specific projects, departments, or other analytic dimensions [2, 7, 8].
     * It is nullable as not all expense lines may require analytic tracking.
     */
    /**
     * @return BelongsTo<AnalyticAccount, static>
     */
    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class, 'analytic_account_id');
    }
}
