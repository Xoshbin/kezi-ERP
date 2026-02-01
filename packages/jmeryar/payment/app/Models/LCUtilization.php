<?php

namespace Jmeryar\Payment\Models;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Jmeryar\Foundation\Observers\AuditLogObserver;
use Jmeryar\Purchase\Models\VendorBill;

/**
 * LC Utilization Model
 *
 * Links a letter of credit to vendor bills showing how the LC is utilized.
 *
 * @property int $id
 * @property int $company_id
 * @property int $letter_of_credit_id
 * @property int $vendor_bill_id
 * @property Money $utilized_amount
 * @property Money $utilized_amount_company_currency
 * @property Carbon $utilization_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read LetterOfCredit $letterOfCredit
 * @property-read VendorBill $vendorBill
 */
#[ObservedBy([AuditLogObserver::class])]
class LCUtilization extends Model
{
    use HasFactory;

    protected $table = 'lc_utilizations';

    protected $fillable = [
        'company_id',
        'letter_of_credit_id',
        'vendor_bill_id',
        'utilized_amount',
        'utilized_amount_company_currency',
        'utilization_date',
    ];

    protected $casts = [
        'utilized_amount' => \Jmeryar\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'utilized_amount_company_currency' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'utilization_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function letterOfCredit(): BelongsTo
    {
        return $this->belongsTo(LetterOfCredit::class);
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    protected static function newFactory()
    {
        return \Jmeryar\Payment\Database\Factories\LCUtilizationFactory::new();
    }
}
