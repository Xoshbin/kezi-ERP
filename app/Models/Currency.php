<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Currency
 *
 * @package App\Models
 *
 * This Eloquent model represents a financial currency within the accounting system.
 * It is essential for supporting multi-currency transactions, tracking exchange rates,
 * and ensuring accurate financial reporting across different monetary denominations.
 *
 * @property int $id Primary key, auto-incrementing [1].
 * @property string $code The ISO 4217 currency code (e.g., 'IQD', 'USD', 'EUR'), unique [1].
 * @property string $name The full name of the currency (e.g., 'Iraqi Dinar', 'United States Dollar') [1].
 * @property string $symbol The symbol of the currency (e.g., 'د.ع', '$') [1].
 * @property float $exchange_rate The rate relative to a chosen base currency (e.g., USD = 1.0) [1].
 * @property bool $is_active Boolean indicating if the currency is currently active, default true [1].
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created [1].
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated [1].
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $companies The companies that use this currency as their default operating currency.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Journal[] $journals The journals that operate in this specific currency.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoices The invoices issued in this currency.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VendorBill[] $vendorBills The vendor bills received in this currency.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Payment[] $payments The payments made or received in this currency.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AnalyticAccount[] $analyticAccounts The analytic accounts that may be specific to this currency.
 */
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
        'last_updated_at'
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
}
