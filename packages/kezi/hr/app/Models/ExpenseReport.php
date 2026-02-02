<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\HR\Enums\ExpenseReportStatus;

/**
 * @property int $id
 * @property int $company_id
 * @property int $cash_advance_id
 * @property int $employee_id
 * @property string $report_number
 * @property \Illuminate\Support\Carbon $report_date
 * @property \Brick\Money\Money $total_amount
 * @property ExpenseReportStatus $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by_user_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read CashAdvance $cashAdvance
 * @property-read Employee $employee
 * @property-read User|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExpenseReportLine> $lines
 */
#[ObservedBy([AuditLogObserver::class])]
class ExpenseReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cash_advance_id',
        'employee_id',
        'report_number',
        'report_date',
        'total_amount',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by_user_id',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'status' => ExpenseReportStatus::class,
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseReportLine::class);
    }

    protected static function newFactory()
    {
        return \Kezi\HR\Database\Factories\ExpenseReportFactory::new();
    }
}
