<?php

namespace Modules\Payment\Models;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Payment\Enums\LetterOfCredit\LCChargeType;

/**
 * LC Charge Model
 *
 * Represents bank charges associated with a letter of credit.
 *
 * @property int $id
 * @property int $company_id
 * @property int $letter_of_credit_id
 * @property int $account_id
 * @property int $currency_id
 * @property int|null $journal_entry_id
 * @property LCChargeType $charge_type
 * @property Money $amount
 * @property Money $amount_company_currency
 * @property Carbon $charge_date
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read LetterOfCredit $letterOfCredit
 * @property-read Account $account
 * @property-read Currency $currency
 * @property-read JournalEntry|null $journalEntry
 */
#[ObservedBy([AuditLogObserver::class])]
class LCCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'letter_of_credit_id',
        'account_id',
        'currency_id',
        'journal_entry_id',
        'charge_type',
        'amount',
        'amount_company_currency',
        'charge_date',
        'description',
    ];

    protected $casts = [
        'charge_type' => LCChargeType::class,
        'amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'amount_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'charge_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function letterOfCredit(): BelongsTo
    {
        return $this->belongsTo(LetterOfCredit::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    protected static function newFactory()
    {
        return \Modules\Payment\Database\Factories\LCChargeFactory::new();
    }
}
