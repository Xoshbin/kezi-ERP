<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Database\Factories\FiscalYearFactory;
use Modules\Accounting\Enums\Accounting\FiscalYearState;

/**
 * Class FiscalYear
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property FiscalYearState $state
 * @property int|null $closing_journal_entry_id
 * @property int|null $closed_by_user_id
 * @property Carbon|null $closed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 * @property-read Collection<int, FiscalPeriod> $periods
 * @property-read JournalEntry|null $closingJournalEntry
 * @property-read User|null $closedBy
 *
 * @method static Builder<static>|FiscalYear newModelQuery()
 * @method static Builder<static>|FiscalYear newQuery()
 * @method static Builder<static>|FiscalYear query()
 * @method static Builder<static>|FiscalYear forCompany(Company $company)
 * @method static Builder<static>|FiscalYear open()
 * @method static Builder<static>|FiscalYear containingDate(Carbon $date)
 * @method static FiscalYearFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class FiscalYear extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'fiscal_years';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'state',
        'closing_journal_entry_id',
        'closed_by_user_id',
        'closed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'state' => FiscalYearState::class,
        'closed_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): FiscalYearFactory
    {
        return FiscalYearFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<FiscalPeriod, static>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class)->orderBy('start_date');
    }

    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function closingJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_entry_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter fiscal years for a specific company.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->id);
    }

    /**
     * Scope to filter only open fiscal years.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('state', FiscalYearState::Open);
    }

    /**
     * Scope to find fiscal year containing a specific date.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeContainingDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if the fiscal year is open.
     */
    public function isOpen(): bool
    {
        return $this->state === FiscalYearState::Open;
    }

    /**
     * Check if the fiscal year is closed.
     */
    public function isClosed(): bool
    {
        return $this->state === FiscalYearState::Closed;
    }

    /**
     * Check if the fiscal year is in draft state.
     */
    public function isDraft(): bool
    {
        return $this->state === FiscalYearState::Draft;
    }

    /**
     * Check if the fiscal year can be closed.
     */
    public function canClose(): bool
    {
        if (! $this->state->canClose()) {
            return false;
        }

        // If using periods, all periods must be closed first
        if ($this->periods()->exists()) {
            return $this->periods()
                ->where('state', '!=', 'closed')
                ->doesntExist();
        }

        return true;
    }

    /**
     * Check if the fiscal year can be reopened.
     */
    public function canReopen(): bool
    {
        return $this->state->canReopen();
    }

    /**
     * Check if a given date falls within this fiscal year.
     */
    public function containsDate(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }
}
