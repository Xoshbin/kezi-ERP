<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Brick\Money\Money;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kezi\Accounting\Enums\Accounting\WithholdingTaxCertificateStatus;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;

// SoftDeletes trait intentionally omitted.
// Posted/issued certificates are immutable; corrections require new certificates.
/**
 * @property int $id
 * @property int $company_id
 * @property string $certificate_number
 * @property int $vendor_id
 * @property Carbon $certificate_date
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property Money $total_base_amount
 * @property Money $total_withheld_amount
 * @property int $currency_id
 * @property WithholdingTaxCertificateStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Partner $vendor
 * @property-read Currency $currency
 * @property-read Collection<int, WithholdingTaxEntry> $entries
 *
 * @method static Builder<static>|WithholdingTaxCertificate newModelQuery()
 * @method static Builder<static>|WithholdingTaxCertificate newQuery()
 * @method static Builder<static>|WithholdingTaxCertificate query()
 * @method static Builder<static>|WithholdingTaxCertificate issued()
 *
 * @property-read int|null $entries_count
 *
 * @method static Builder<static>|WithholdingTaxCertificate whereCertificateDate($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereCertificateNumber($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereCompanyId($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereCreatedAt($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereCurrencyId($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereId($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereNotes($value)
 * @method static Builder<static>|WithholdingTaxCertificate wherePeriodEnd($value)
 * @method static Builder<static>|WithholdingTaxCertificate wherePeriodStart($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereStatus($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereTotalBaseAmount($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereTotalWithheldAmount($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereUpdatedAt($value)
 * @method static Builder<static>|WithholdingTaxCertificate whereVendorId($value)
 * @method static \Kezi\Accounting\Database\Factories\WithholdingTaxCertificateFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class WithholdingTaxCertificate extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\WithholdingTaxCertificateFactory
    {
        return \Kezi\Accounting\Database\Factories\WithholdingTaxCertificateFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'withholding_tax_certificates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'certificate_number',
        'vendor_id',
        'certificate_date',
        'period_start',
        'period_end',
        'total_base_amount',
        'total_withheld_amount',
        'currency_id',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'certificate_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'total_base_amount' => BaseCurrencyMoneyCast::class,
        'total_withheld_amount' => BaseCurrencyMoneyCast::class,
        'status' => WithholdingTaxCertificateStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the Company that owns this certificate.
     *
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Vendor (Partner) this certificate is for.
     *
     * @return BelongsTo<Partner, static>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    /**
     * Get the Currency of this certificate.
     *
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the WHT entries included in this certificate.
     *
     * @return HasMany<WithholdingTaxEntry, static>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(WithholdingTaxEntry::class, 'withholding_tax_certificate_id');
    }

    /**
     * Scope a query to only include issued certificates.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIssued($query)
    {
        return $query->where('status', WithholdingTaxCertificateStatus::Issued);
    }

    /**
     * Check if the certificate is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === WithholdingTaxCertificateStatus::Draft;
    }

    /**
     * Check if the certificate has been issued.
     */
    public function isIssued(): bool
    {
        return $this->status === WithholdingTaxCertificateStatus::Issued;
    }

    /**
     * Check if the certificate has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === WithholdingTaxCertificateStatus::Cancelled;
    }

    /**
     * Check if the certificate can be modified.
     */
    public function canBeModified(): bool
    {
        return $this->isDraft();
    }
}
