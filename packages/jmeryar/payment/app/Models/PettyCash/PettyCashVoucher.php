<?php

namespace Jmeryar\Payment\Models\PettyCash;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Foundation\Observers\AuditLogObserver;
use Jmeryar\Payment\Enums\PettyCash\PettyCashVoucherStatus;

/**
 * @property int $id
 * @property int $company_id
 * @property int $fund_id
 * @property string $voucher_number
 * @property int $expense_account_id
 * @property int|null $partner_id
 * @property \Brick\Money\Money $amount
 * @property \Illuminate\Support\Carbon $voucher_date
 * @property string $description
 * @property string|null $receipt_reference
 * @property PettyCashVoucherStatus $status
 * @property int|null $journal_entry_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read PettyCashFund $fund
 * @property-read Account $expenseAccount
 * @property-read Partner|null $partner
 * @property-read JournalEntry|null $journalEntry
 */
#[ObservedBy([AuditLogObserver::class])]
class PettyCashVoucher extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Jmeryar\Payment\Database\Factories\PettyCash\PettyCashVoucherFactory::new();
    }

    protected $fillable = [
        'company_id',
        'fund_id',
        'voucher_number',
        'expense_account_id',
        'partner_id',
        'amount',
        'voucher_date',
        'description',
        'receipt_reference',
        'status',
        'journal_entry_id',
    ];

    protected $casts = [
        'amount' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'voucher_date' => 'date',
        'status' => PettyCashVoucherStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'fund_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
