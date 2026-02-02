<?php

namespace Kezi\Payment\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\Payment\Enums\Cheques\ChequeStatus;
use Kezi\Payment\Enums\Cheques\ChequeType;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $chequebook_id
 * @property int $journal_id
 * @property int $partner_id
 * @property int $currency_id
 * @property int|null $payment_id
 * @property int|null $journal_entry_id
 * @property string $cheque_number
 * @property \Brick\Money\Money $amount
 * @property \Brick\Money\Money $amount_company_currency
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property ChequeStatus $status
 * @property ChequeType $type
 * @property string $payee_name
 * @property string|null $bank_name
 * @property string|null $memo
 * @property \Illuminate\Support\Carbon|null $deposited_at
 * @property \Illuminate\Support\Carbon|null $cleared_at
 * @property \Illuminate\Support\Carbon|null $bounced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 */
#[ObservedBy([AuditLogObserver::class])]
class Cheque extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'chequebook_id',
        'journal_id',
        'partner_id',
        'currency_id',
        'payment_id',
        'journal_entry_id',
        'cheque_number',
        'amount',
        'amount_company_currency',
        'issue_date',
        'due_date',
        'status',
        'type',
        'payee_name',
        'bank_name',
        'memo',
        'deposited_at',
        'cleared_at',
        'bounced_at',
    ];

    protected $casts = [
        'amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'amount_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'issue_date' => 'date',
        'due_date' => 'date',
        'deposited_at' => 'datetime',
        'cleared_at' => 'datetime',
        'bounced_at' => 'datetime',
        'status' => ChequeStatus::class,
        'type' => ChequeType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chequebook(): BelongsTo
    {
        return $this->belongsTo(Chequebook::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function bouncedLogs(): HasMany
    {
        return $this->hasMany(ChequeBouncedLog::class);
    }

    protected static function newFactory()
    {
        return \Kezi\Payment\Database\Factories\ChequeFactory::new();
    }
}
