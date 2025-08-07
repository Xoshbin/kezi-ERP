<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\VendorBill;
use App\Observers\CurrencyObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * Class Currency
 *
 * @package App\Models
 *
 * This Eloquent model represents a financial currency within the accounting system.
 * It is essential for supporting multi-currency transactions, tracking exchange rates,
 * and ensuring accurate financial reporting across different monetary denominations.
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property float $exchange_rate
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AnalyticAccount> $analyticAccounts
 * @property-read int|null $analytic_accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Company> $companies
 * @property-read int|null $companies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Journal> $journals
 * @property-read int|null $journals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @method static \Database\Factories\CurrencyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereLastUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereUpdatedAt($value)
 * @mixin \Eloquent
 */

#[ObservedBy([CurrencyObserver::class])]
class Currency extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Currencies are fundamental for multi-national or multi-currency operations.
     *
     * @var string
     */
    protected $table = 'currencies'; // [1]

    /**
     * The attributes that are mass assignable.
     * These fields define the core properties of a currency.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_active',
        'last_updated_at',
        'decimal_places'
    ];

    /**
     * The attributes that should be cast.
     * Ensures numerical values are treated as floats and timestamps as Carbon instances.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exchange_rate' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'decimal_places' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | A currency can be the default for companies, or specified for various financial documents.
    */

    /**
     * Get the companies that use this currency as their default operating currency.
     * A single currency can be the base currency for multiple companies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class); // [1]
    }

    /**
     * Get the journals that operate in this specific currency.
     * Some journals may be configured to handle transactions in a single currency only.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class); // [1]
    }

    /**
     * Get the invoices issued in this currency.
     * Transactions like invoices can be in a currency different from the company's default.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class); // [1]
    }

    /**
     * Get the vendor bills received in this currency.
     * Vendor bills, similar to invoices, can be in various currencies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class); // [1]
    }

    /**
     * Get the payments made or received in this currency.
     * Payments track the actual cash movement in a specific currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class); // [1]
    }

    /**
     * Get the analytic accounts that may be specific to this currency.
     * While not all analytic accounts require a specific currency, some might for project-specific budgeting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analyticAccounts(): HasMany
    {
        return $this->hasMany(AnalyticAccount::class); // [1]
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
