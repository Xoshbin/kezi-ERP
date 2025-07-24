<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\InvoiceLineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'invoice_id',          // Foreign key to the parent invoice [3, 4]
        'product_id',          // Foreign key to the product (nullable) [3, 4]
        'description',         // Text description for the line item [3, 4]
        'quantity',            // Quantity of the product/service [3, 4]
        'unit_price',          // Price per unit [3, 4]
        'tax_id',              // Foreign key to the tax applied (nullable) [3, 4]
        'subtotal',            // Calculated subtotal for the line (quantity * unit_price) [3, 4]
        'total_line_tax',      // Total tax amount for this line [3, 4]
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
        'subtotal' => MoneyCast::class,
        'total_line_tax' => MoneyCast::class,
        'created_at' => 'datetime', // Eloquent automatically manages these, but explicit casting is robust [12, 13].
        'updated_at' => 'datetime',
    ];

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
}
