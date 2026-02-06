<?php

namespace Kezi\ProjectManagement\Models;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kezi\Sales\Models\Invoice;

/**
 * Class ProjectInvoice
 *
 * @property int $id
 * @property int $company_id
 * @property int $project_id
 * @property int|null $invoice_id
 * @property Carbon $invoice_date
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property \Brick\Money\Money $labor_amount
 * @property \Brick\Money\Money $expense_amount
 * @property \Brick\Money\Money $total_amount
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Project $project
 * @property-read Invoice|null $invoice
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereExpenseAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereLaborAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectInvoice whereUpdatedAt($value)
 * @method static \Kezi\ProjectManagement\Database\Factories\ProjectInvoiceFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([\Kezi\Foundation\Observers\AuditLogObserver::class])]
class ProjectInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'invoice_id',
        'invoice_date',
        'period_start',
        'period_end',
        'labor_amount',
        'expense_amount',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'labor_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'expense_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'total_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'labor_amount' => '0',
        'expense_amount' => '0',
        'total_amount' => '0',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Project, static>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Invoice, static>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get labor amount as Money object.
     */
    public function getLaborMoney(): Money
    {
        return Money::ofMinor($this->labor_amount, $this->company->currency->code);
    }

    /**
     * Get expense amount as Money object.
     */
    public function getExpenseMoney(): Money
    {
        return Money::ofMinor($this->expense_amount, $this->company->currency->code);
    }

    /**
     * Get total amount as Money object.
     */
    public function getTotalMoney(): Money
    {
        return Money::ofMinor($this->total_amount, $this->company->currency->code);
    }

    /**
     * Check if invoice has been created.
     */
    public function isInvoiced(): bool
    {
        return $this->status === 'invoiced' && $this->invoice_id !== null;
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    protected static function newFactory()
    {
        return \Kezi\ProjectManagement\Database\Factories\ProjectInvoiceFactory::new();
    }
}
