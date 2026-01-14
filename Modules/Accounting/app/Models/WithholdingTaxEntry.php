<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use Brick\Money\Money;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Models\Payment;

// SoftDeletes trait intentionally omitted for audit trail integrity.
/**
 * @property int $id
 * @property int $company_id
 * @property int $payment_id
 * @property int $withholding_tax_type_id
 * @property int $vendor_id
 * @property Money $base_amount
 * @property Money $withheld_amount
 * @property float $rate_applied
 * @property int $currency_id
 * @property int|null $withholding_tax_certificate_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Payment $payment
 * @property-read WithholdingTaxType $withholdingTaxType
 * @property-read Partner $vendor
 * @property-read Currency $currency
 * @property-read WithholdingTaxCertificate|null $certificate
 *
 * @method static Builder<static>|WithholdingTaxEntry newModelQuery()
 * @method static Builder<static>|WithholdingTaxEntry newQuery()
 * @method static Builder<static>|WithholdingTaxEntry query()
 * @method static Builder<static>|WithholdingTaxEntry uncertified()
 *
 * @mixin Eloquent
 */
class WithholdingTaxEntry extends Model
{
    use HasFactory;

    protected static function newFactory(): \Modules\Accounting\Database\Factories\WithholdingTaxEntryFactory
    {
        return \Modules\Accounting\Database\Factories\WithholdingTaxEntryFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'withholding_tax_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'payment_id',
        'withholding_tax_type_id',
        'vendor_id',
        'base_amount',
        'withheld_amount',
        'rate_applied',
        'currency_id',
        'withholding_tax_certificate_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_amount' => BaseCurrencyMoneyCast::class,
        'withheld_amount' => BaseCurrencyMoneyCast::class,
        'rate_applied' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the Company that owns this entry.
     *
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Payment this entry is linked to.
     *
     * @return BelongsTo<Payment, static>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Get the WithholdingTaxType for this entry.
     *
     * @return BelongsTo<WithholdingTaxType, static>
     */
    public function withholdingTaxType(): BelongsTo
    {
        return $this->belongsTo(WithholdingTaxType::class, 'withholding_tax_type_id');
    }

    /**
     * Get the Vendor (Partner) this WHT was withheld from.
     *
     * @return BelongsTo<Partner, static>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    /**
     * Get the Currency of this entry.
     *
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the Certificate this entry is linked to (if any).
     *
     * @return BelongsTo<WithholdingTaxCertificate, static>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(WithholdingTaxCertificate::class, 'withholding_tax_certificate_id');
    }

    /**
     * Scope a query to only include uncertified entries.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeUncertified($query)
    {
        return $query->whereNull('withholding_tax_certificate_id');
    }

    /**
     * Check if this entry has been certified.
     */
    public function isCertified(): bool
    {
        return $this->withholding_tax_certificate_id !== null;
    }

    /**
     * Get the rate as a percentage for display.
     */
    public function getRatePercentageAttribute(): float
    {
        return (float) $this->rate_applied * 100;
    }
}
