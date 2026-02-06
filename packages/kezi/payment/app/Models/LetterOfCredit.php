<?php

namespace Kezi\Payment\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Enums\LetterOfCredit\LCType;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\VendorBill;

/**
 * Letter of Credit Model
 *
 * Represents a letter of credit for import/export transactions.
 * Tracks LC lifecycle from draft through issuance to utilization.
 *
 * @property int $id
 * @property int $company_id
 * @property int $vendor_id
 * @property int|null $issuing_bank_partner_id
 * @property int $currency_id
 * @property int|null $purchase_order_id
 * @property int $created_by_user_id
 * @property string $lc_number
 * @property string|null $bank_reference
 * @property LCType $type
 * @property LCStatus $status
 * @property Money $amount
 * @property Money $amount_company_currency
 * @property Money $utilized_amount
 * @property Money $balance
 * @property Carbon $issue_date
 * @property Carbon $expiry_date
 * @property Carbon|null $shipment_date
 * @property string|null $incoterm
 * @property string|null $terms_and_conditions
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Partner $vendor
 * @property-read Partner|null $issuingBank
 * @property-read Currency $currency
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read User $createdByUser
 * @property-read Collection<int, LCCharge> $charges
 * @property-read Collection<int, LCUtilization> $utilizations
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $charges_count
 * @property-read int|null $utilizations_count
 * @property-read int|null $vendor_bills_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereAmountCompanyCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereBankReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereIncoterm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereIssuingBankPartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereLcNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereShipmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereTermsAndConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereUtilizedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LetterOfCredit whereVendorId($value)
 * @method static \Kezi\Payment\Database\Factories\LetterOfCreditFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class LetterOfCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'issuing_bank_partner_id',
        'currency_id',
        'purchase_order_id',
        'created_by_user_id',
        'lc_number',
        'bank_reference',
        'type',
        'status',
        'amount',
        'amount_company_currency',
        'utilized_amount',
        'balance',
        'issue_date',
        'expiry_date',
        'shipment_date',
        'incoterm',
        'terms_and_conditions',
        'notes',
    ];

    protected $casts = [
        'type' => LCType::class,
        'status' => LCStatus::class,
        'amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'amount_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'utilized_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'balance' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'shipment_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    public function issuingBank(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'issuing_bank_partner_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(LCCharge::class);
    }

    public function utilizations(): HasMany
    {
        return $this->hasMany(LCUtilization::class);
    }

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    /**
     * Update utilized amount and balance based on utilizations
     */
    public function recalculateBalance(): void
    {
        $this->load('utilizations');

        $currency = $this->currency;
        $utilized = \Brick\Money\Money::of(0, $currency->code);

        foreach ($this->utilizations as $utilization) {
            $utilized = $utilized->plus($utilization->utilized_amount);
        }

        $this->utilized_amount = $utilized;
        $this->balance = $this->amount->minus($utilized);

        if ($this->balance->isZero()) {
            $this->status = LCStatus::FullyUtilized;
        } elseif ($this->utilized_amount->isPositive()) {
            $this->status = LCStatus::PartiallyUtilized;
        }

        $this->save();
    }

    /**
     * Check if LC is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast() && $this->status !== LCStatus::FullyUtilized;
    }

    /**
     * Check if LC can be utilized
     */
    public function canBeUtilized(): bool
    {
        return in_array($this->status, [
            LCStatus::Issued,
            LCStatus::PartiallyUtilized,
        ]) && ! $this->isExpired();
    }

    protected static function newFactory()
    {
        return \Kezi\Payment\Database\Factories\LetterOfCreditFactory::new();
    }
}
