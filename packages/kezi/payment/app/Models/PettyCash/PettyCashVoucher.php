<?php

namespace Kezi\Payment\Models\PettyCash;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\Payment\Enums\PettyCash\PettyCashVoucherStatus;

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
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher wherePartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereReceiptReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereVoucherDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PettyCashVoucher whereVoucherNumber($value)
 * @method static \Kezi\Payment\Database\Factories\PettyCash\PettyCashVoucherFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class PettyCashVoucher extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Kezi\Payment\Database\Factories\PettyCash\PettyCashVoucherFactory::new();
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
        'amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
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
