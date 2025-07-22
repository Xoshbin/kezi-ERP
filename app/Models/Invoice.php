<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Invoice
 *
 * @package App\Models
 *
 * This Eloquent model represents a customer invoice in the accounting system.
 * It is a foundational document for recording sales transactions and receivables.
 * Key accounting principles, such as immutability post-posting and a clear
 * audit trail for changes or corrections, are deeply embedded in its design.
 *
 * @property int $id Primary key, auto-incrementing. [1]
 * @property int $company_id Foreign key to the 'companies' table, indicating which company issued the invoice. [1]
 * @property int $customer_id Foreign key to the 'partners' table, representing the customer to whom the invoice is issued. [1]
 * @property Carbon $invoice_date The date the invoice was issued. [1]
 * @property Carbon $due_date The date the invoice payment is due. [1]
 * @property string|null $invoice_number A unique, sequentially assigned invoice number. This is assigned only upon confirmation/posting. [1, 2]
 * @property string $status The current status of the invoice (e.g., 'Draft', 'Posted', 'Paid', 'Cancelled'). 'Posted' invoices are immutable. [1, 3]
 * @property int $currency_id Foreign key to the 'currencies' table, representing the currency of the invoice. [1]
 * @property float $total_amount The total amount of the invoice, including tax. [1]
 * @property float $total_tax The total tax amount on the invoice. [1]
 * @property int|null $journal_entry_id Nullable foreign key to the 'journal_entries' table. Links the invoice to its immutable financial record once posted. [1]
 * @property Carbon|null $created_at Timestamp when the record was created. [1]
 * @property Carbon|null $updated_at Timestamp when the record was last updated. [1]
 * @property Carbon|null $posted_at Nullable timestamp, records when the invoice's status changed to 'Posted'. [1]
 * @property string|null $reset_to_draft_log JSON/Text field to log instances where a 'Posted' invoice was reset to 'Draft', crucial for audit trail maintenance. [1]
 * @property int|null $fiscal_position_id Nullable foreign key to the 'fiscal_positions' table. [4]
 *
 * @property-read \App\Models\Company $company The company that issued this invoice.
 * @property-read \App\Models\Partner $customer The customer to whom this invoice is issued.
 * @property-read \App\Models\Currency $currency The currency of this invoice.
 * @property-read \App\Models\JournalEntry|null $journalEntry The associated journal entry once the invoice is posted.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InvoiceLine[] $invoiceLines The individual line items of this invoice.
 * @property-read \App\Models\FiscalPosition|null $fiscalPosition The fiscal position applied to this invoice.
 */
class Invoice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * The sources indicate this model maps to the 'invoices' table. [1, 5]
     *
     * @var string
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     * These fields can be filled via mass assignment. Fields like 'invoice_number',
     * 'status', 'journal_entry_id', 'posted_at', and 'reset_to_draft_log' are
     * typically managed internally by the application's logic upon confirmation
     * or posting, rather than being directly mass-assigned from user input. [1, 6]
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'currency_id',
        'journal_entry_id',
        'fiscal_position_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'total_amount',
        'total_tax',
        'posted_at',
        'reset_to_draft_log',
    ];

    /**
     * The attributes that should be cast.
     * Ensures dates are Carbon instances and JSON fields are properly handled. [1]
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'float',
        'total_tax' => 'float',
        'reset_to_draft_log' => 'json', // Store as JSON/Text as per source [1]
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | These methods define the relationships this Invoice model has with other
    | entities in the accounting system, crucial for data integrity and navigation.
    */

    /**
     * Get the company that owns this invoice.
     * An invoice is always issued by a specific company. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer (partner) associated with this invoice.
     * The customer is the entity to whom the invoice is issued. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the currency of this invoice.
     * Every invoice operates in a specific currency. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entry associated with this invoice once it is posted.
     * This link is vital for the immutability of financial records. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the line items for this invoice.
     * An invoice typically consists of multiple product or service lines. [1]
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Get the fiscal position applied to this invoice.
     * Fiscal positions can automatically adapt taxes and accounts based on specific rules. [4, 7]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fiscalPosition(): BelongsTo
    {
        return $this->belongsTo(FiscalPosition::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Example)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include posted invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'Posted');
    }

    /**
     * Scope a query to only include draft invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators (Example)
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full invoice reference, combining number and date for display.
     *
     * @return string
     */
    public function getFullReferenceAttribute(): string
    {
        return $this->invoice_number . ' - ' . $this->invoice_date->format('Y-m-d');
    }
}
