<?php

namespace App\Models;

use App\Observers\CurrencyObserver;
use App\Traits\TranslatableSearch;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Class Currency
 *
 * @property int $id
 * @property string $code
 * @property string|array<string, string> $name
 * @property string $symbol
 * @property float $exchange_rate
 * @property bool $is_active
 * @property Carbon|null $last_updated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, AnalyticAccount> $analyticAccounts
 * @property-read int|null $analytic_accounts_count
 * @property-read Collection<int, Company> $companies
 * @property-read int|null $companies_count
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, Journal> $journals
 * @property-read int|null $journals_count
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 *
 * @method static CurrencyFactory factory($count = null, $state = [])
 * @method static Builder<static>|Currency newModelQuery()
 * @method static Builder<static>|Currency newQuery()
 * @method static Builder<static>|Currency query()
 * @method static Builder<static>|Currency whereCode($value)
 * @method static Builder<static>|Currency whereCreatedAt($value)
 * @method static Builder<static>|Currency whereExchangeRate($value)
 * @method static Builder<static>|Currency whereId($value)
 * @method static Builder<static>|Currency whereIsActive($value)
 * @method static Builder<static>|Currency whereLastUpdatedAt($value)
 * @method static Builder<static>|Currency whereName($value)
 * @method static Builder<static>|Currency whereSymbol($value)
 * @method static Builder<static>|Currency whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy([CurrencyObserver::class])]
class Currency extends Model
{
    use HasFactory, HasTranslations, TranslatableSearch;

    /** @var array<int, string> */
    public array $translatable = ['name'];

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
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'last_updated_at',
        'decimal_places',
    ];

    /**
     * The attributes that should be cast.
     * Ensures numerical values are treated as floats and timestamps as Carbon instances.
     *
     * @var array<string, string>
     */
    protected $casts = [
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
     */
    /**

     * @return HasMany<Company, static>

     */

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class); // [1]
    }

    /**
     * Get the journals that operate in this specific currency.
     * Some journals may be configured to handle transactions in a single currency only.
     */
    /**

     * @return HasMany<Journal, static>

     */

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class); // [1]
    }

    /**
     * Get the invoices issued in this currency.
     * Transactions like invoices can be in a currency different from the company's default.
     */
    /**

     * @return HasMany<Invoice, static>

     */

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class); // [1]
    }

    /**
     * Get the vendor bills received in this currency.
     * Vendor bills, similar to invoices, can be in various currencies.
     */
    /**

     * @return HasMany<VendorBill, static>

     */

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class); // [1]
    }

    /**
     * Get the payments made or received in this currency.
     * Payments track the actual cash movement in a specific currency.
     */
    /**

     * @return HasMany<Payment, static>

     */

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class); // [1]
    }

    /**
     * Get the analytic accounts that may be specific to this currency.
     * While not all analytic accounts require a specific currency, some might for project-specific budgeting.
     */
    /**

     * @return HasMany<AnalyticAccount, static>

     */

    public function analyticAccounts(): HasMany
    {
        return $this->hasMany(AnalyticAccount::class); // [1]
    }

    /**


     * @return HasMany<JournalEntry, static>


     */


    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Get the historical exchange rates for this currency.
     * This relationship provides access to all historical exchange rate data.
     */
    /**

     * @return HasMany<CurrencyRate, static>

     */

    public function rates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class);
    }

    /**
     * Get the latest exchange rate for this currency.
     */
    /**

     * @return HasOne<CurrencyRate, static>

     */

    public function latestRate(): HasOne
    {
        return $this->hasOne(CurrencyRate::class)->latestOfMany('effective_date');
    }
}
