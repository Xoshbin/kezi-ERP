<?php

namespace Kezi\Accounting\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Database\Factories\FiscalPeriodFactory;
use Kezi\Accounting\Enums\Accounting\FiscalPeriodState;

/**
 * Class FiscalPeriod
 *
 * @property int $id
 * @property int $fiscal_year_id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property FiscalPeriodState $state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read FiscalYear $fiscalYear
 *
 * @method static Builder<static>|FiscalPeriod newModelQuery()
 * @method static Builder<static>|FiscalPeriod newQuery()
 * @method static Builder<static>|FiscalPeriod query()
 * @method static Builder<static>|FiscalPeriod open()
 * @method static FiscalPeriodFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class FiscalPeriod extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'fiscal_periods';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'fiscal_year_id',
        'name',
        'start_date',
        'end_date',
        'state',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'state' => FiscalPeriodState::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): FiscalPeriodFactory
    {
        return FiscalPeriodFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * @return BelongsTo<FiscalYear, static>
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter only open fiscal periods.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('state', FiscalPeriodState::Open);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if the fiscal period is open.
     */
    public function isOpen(): bool
    {
        return $this->state === FiscalPeriodState::Open;
    }

    /**
     * Check if the fiscal period is closed.
     */
    public function isClosed(): bool
    {
        return $this->state === FiscalPeriodState::Closed;
    }

    /**
     * Check if the fiscal period can be closed.
     */
    public function canClose(): bool
    {
        return $this->state->canClose();
    }

    /**
     * Check if a given date falls within this fiscal period.
     */
    public function containsDate(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }
}
