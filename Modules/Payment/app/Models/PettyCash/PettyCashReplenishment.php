<?php

namespace Modules\Payment\Models\PettyCash;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Observers\AuditLogObserver;

/**
 * @property int $id
 * @property int $company_id
 * @property int $fund_id
 * @property string $replenishment_number
 * @property \Brick\Money\Money $amount
 * @property \Illuminate\Support\Carbon $replenishment_date
 * @property string $payment_method
 * @property string|null $reference
 * @property int|null $journal_entry_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read PettyCashFund $fund
 * @property-read JournalEntry|null $journalEntry
 */
#[ObservedBy([AuditLogObserver::class])]
class PettyCashReplenishment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Modules\Payment\Database\Factories\PettyCash\PettyCashReplenishmentFactory::new();
    }

    protected $fillable = [
        'company_id',
        'fund_id',
        'replenishment_number',
        'amount',
        'replenishment_date',
        'payment_method',
        'reference',
        'journal_entry_id',
    ];

    protected $casts = [
        'amount' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'replenishment_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'fund_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
