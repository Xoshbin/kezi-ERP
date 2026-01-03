<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Enums\Currency\RevaluationStatus;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Modules\Foundation\Observers\AuditLogObserver;

/**
 * Currency Revaluation Model
 *
 * Tracks period-end revaluations of foreign currency balances.
 * This is critical for accurate balance sheet reporting of foreign currency
 * assets and liabilities at current exchange rates.
 *
 * @property int $id
 * @property int $company_id
 * @property int $created_by_user_id
 * @property int|null $journal_entry_id
 * @property \Illuminate\Support\Carbon $revaluation_date
 * @property string|null $reference
 * @property string|null $description
 * @property RevaluationStatus $status
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property Money $total_gain
 * @property Money $total_loss
 * @property Money $net_adjustment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read User $createdBy
 * @property-read JournalEntry|null $journalEntry
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CurrencyRevaluationLine> $lines
 */
#[ObservedBy([AuditLogObserver::class])]
class CurrencyRevaluation extends Model
{
    use HasFactory;

    protected $table = 'currency_revaluations';

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'journal_entry_id',
        'revaluation_date',
        'reference',
        'description',
        'status',
        'posted_at',
        'total_gain',
        'total_loss',
        'net_adjustment',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'revaluation_date' => 'date',
        'posted_at' => 'datetime',
        'status' => RevaluationStatus::class,
        'total_gain' => BaseCurrencyMoneyCast::class,
        'total_loss' => BaseCurrencyMoneyCast::class,
        'net_adjustment' => BaseCurrencyMoneyCast::class,
    ];

    /** @var list<string> */
    protected $with = ['company.currency'];

    // Relationships

    /** @return BelongsTo<Company, static> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, static> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<JournalEntry, static> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return HasMany<CurrencyRevaluationLine, static> */
    public function lines(): HasMany
    {
        return $this->hasMany(CurrencyRevaluationLine::class);
    }

    // Business Logic

    public function isPosted(): bool
    {
        return $this->status === RevaluationStatus::Posted;
    }

    public function isDraft(): bool
    {
        return $this->status === RevaluationStatus::Draft;
    }

    public function isReversed(): bool
    {
        return $this->status === RevaluationStatus::Reversed;
    }

    public function canBeModified(): bool
    {
        return $this->status->isEditable();
    }

    public function canBePosted(): bool
    {
        return $this->status->canBePosted() && $this->lines()->exists();
    }

    public function canBeReversed(): bool
    {
        return $this->status->canBeReversed();
    }

    /**
     * Check if there's a net gain from this revaluation.
     */
    public function isNetGain(): bool
    {
        return $this->net_adjustment->isPositive();
    }

    /**
     * Check if there's a net loss from this revaluation.
     */
    public function isNetLoss(): bool
    {
        return $this->net_adjustment->isNegative();
    }
}

