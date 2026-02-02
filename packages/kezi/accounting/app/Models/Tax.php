<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Observers\TaxObserver;
use Kezi\Purchase\Models\VendorBillLine;
use Kezi\Sales\Models\InvoiceLine;
use Spatie\Translatable\HasTranslations;

// The SoftDeletes trait is intentionally omitted for the Tax model.
// As per accounting principles, tax records, once used, should not be physically deleted.
// Instead, they are managed via an 'is_active' flag for historical auditability.
/**
 * @property int $id
 * @property int $company_id
 * @property int $tax_account_id
 * @property string|array<string, string> $name
 * @property string|array<string, string>|null $label_on_invoices
 * @property float $rate
 * @property TaxType $type
 * @property bool $is_active
 * @property bool $is_group
 * @property string|null $country
 * @property string|null $report_tag
 * @property bool $is_recoverable
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read float $rate_percentage
 * @property-read Account $taxAccount
 *
 * @method static Builder<static>|Tax active()
 * @method static TaxFactory factory($count = null, $state = [])
 * @method static Builder<static>|Tax newModelQuery()
 * @method static Builder<static>|Tax newQuery()
 * @method static Builder<static>|Tax query()
 * @method static Builder<static>|Tax whereCompanyId($value)
 * @method static Builder<static>|Tax whereCreatedAt($value)
 * @method static Builder<static>|Tax whereId($value)
 * @method static Builder<static>|Tax whereIsActive($value)
 * @method static Builder<static>|Tax whereName($value)
 * @method static Builder<static>|Tax whereRate($value)
 * @method static Builder<static>|Tax whereTaxAccountId($value)
 * @method static Builder<static>|Tax whereType($value)
 * @method static Builder<static>|Tax whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
#[ObservedBy([TaxObserver::class])]

class Tax extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\TaxFactory
    {
        return \Kezi\Accounting\Database\Factories\TaxFactory::new();
    }

    /** @var array<int, string> */
    public array $translatable = ['name', 'label_on_invoices'];

    /**
     * Get the translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return ['name', 'label_on_invoices'];
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'taxes'; // Explicitly defining the table name for clarity.

    /**
     * The attributes that are mass assignable.
     * These fields are intended for direct assignment, often from user input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'type', // e.g., 'Sales', 'Purchase', 'Both' [1]
        'is_active',
        'is_group',
        'country',
        'report_tag',
        'is_recoverable', // Whether tax can be deducted as input tax or should be capitalized
        'tax_account_id', // Foreign key to the Account model for ledger posting [1]
    ];

    /**
     * The attributes that should be cast.
     * This ensures proper data type handling for precision and logical representation.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'float', // Crucial for monetary precision in tax calculations [1]
        'is_active' => 'boolean', // Ensures boolean behavior for the active status [1]
        'is_group' => 'boolean',
        'is_recoverable' => 'boolean', // Whether tax can be deducted as input tax or should be capitalized
        'type' => TaxType::class,
        'created_at' => 'datetime', // Laravel automatically casts these, but explicit declaration is good practice.
        'updated_at' => 'datetime',
    ];

    /**
     * Get the Company that owns the Tax.
     * This relationship enforces the multi-company architecture, ensuring that each tax
     * is correctly associated with a specific legal entity for localized compliance.
     *
     * @return BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Account from the Chart of Accounts where this tax's amounts are posted.
     * This is a fundamental link for automating journal entries (e.g., VAT Payable/Receivable)
     * and ensures accurate financial reporting and reconciliation.
     *
     * @return BelongsTo
     */
    public function taxAccount()
    {
        return $this->belongsTo(Account::class, 'tax_account_id');
    }

    /**
     * Get the child taxes that make up this tax group.
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'tax_components', 'parent_tax_id', 'child_tax_id');
    }

    /**
     * Get the parent taxes that this tax belongs to.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'tax_components', 'child_tax_id', 'parent_tax_id');
    }

    /**
     * Scope a query to only include active taxes.
     * This local scope is vital for filtering taxes that are currently in use,
     * reflecting the principle that taxes are deactivated rather than deleted
     * to preserve historical transaction integrity [2].
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor to get the tax rate as a percentage for display purposes.
     */
    public function getRatePercentageAttribute(): float
    {
        return (float) $this->rate * 100; // 0.15 -> 15.00%
    }

    public function getRateFractionAttribute(): float
    {
        return (float) $this->rate; // 0.15 -> 0.15
    }

    /**
     * Determine if the tax is applicable to sales transactions.
     */
    public function isSalesTax(): bool
    {
        return in_array($this->type, [TaxType::Sales, TaxType::Both]);
    }

    /**
     * Determine if the tax is applicable to purchase transactions.
     */
    public function isPurchaseTax(): bool
    {
        return in_array($this->type, [TaxType::Purchase, TaxType::Both]);
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
}
