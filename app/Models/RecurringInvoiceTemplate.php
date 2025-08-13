<?php

namespace App\Models;

use App\Enums\RecurringInvoice\RecurringFrequency;
use App\Enums\RecurringInvoice\RecurringStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Class RecurringInvoiceTemplate
 *
 * @package App\Models
 *
 * This model represents a template for generating recurring inter-company invoices.
 * It stores the configuration for automatic generation of invoices and corresponding
 * vendor bills between related companies for shared services like management fees.
 *
 * @property int $id
 * @property int $company_id
 * @property int $target_company_id
 * @property string $name
 * @property string|null $description
 * @property string $reference_prefix
 * @property string $frequency
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon $next_run_date
 * @property int $day_of_month
 * @property int $month_of_quarter
 * @property string $status
 * @property bool $is_active
 * @property int $currency_id
 * @property int $income_account_id
 * @property int $expense_account_id
 * @property int|null $tax_id
 * @property array $template_data
 * @property int $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon|null $last_generated_at
 * @property int $generation_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RecurringInvoiceTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'target_company_id',
        'name',
        'description',
        'reference_prefix',
        'frequency',
        'start_date',
        'end_date',
        'next_run_date',
        'day_of_month',
        'month_of_quarter',
        'status',
        'is_active',
        'currency_id',
        'income_account_id',
        'expense_account_id',
        'tax_id',
        'template_data',
        'created_by_user_id',
        'updated_by_user_id',
        'last_generated_at',
        'generation_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'frequency' => RecurringFrequency::class,
        'status' => RecurringStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'is_active' => 'boolean',
        'template_data' => 'json',
        'last_generated_at' => 'datetime',
        'generation_count' => 'integer',
        'day_of_month' => 'integer',
        'month_of_quarter' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'reference_prefix' => 'IC-RECURRING',
        'status' => 'active',
        'is_active' => true,
        'generation_count' => 0,
        'day_of_month' => 1,
        'month_of_quarter' => 1,
    ];

    /**
     * The company that owns this template (the one generating invoices).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * The target company that will receive the invoices.
     */
    public function targetCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }

    /**
     * The currency for this template.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * The income account for the generating company.
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    /**
     * The expense account for the target company.
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    /**
     * The tax configuration for this template.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * The user who created this template.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * The user who last updated this template.
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * The invoices generated from this template.
     */
    public function generatedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_template_id');
    }

    /**
     * The vendor bills generated from this template.
     */
    public function generatedVendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class, 'recurring_template_id');
    }

    /**
     * Check if the template is due for generation.
     */
    public function isDue(): bool
    {
        return $this->is_active 
            && $this->status === RecurringStatus::Active
            && $this->next_run_date <= now()->toDateString()
            && ($this->end_date === null || $this->next_run_date <= $this->end_date);
    }

    /**
     * Calculate the next run date based on frequency.
     */
    public function calculateNextRunDate(): Carbon
    {
        $currentDate = Carbon::parse($this->next_run_date);
        
        return match ($this->frequency) {
            RecurringFrequency::Monthly => $currentDate->addMonth()->day($this->day_of_month),
            RecurringFrequency::Quarterly => $currentDate->addMonths(3)->day($this->day_of_month),
            RecurringFrequency::Yearly => $currentDate->addYear()->day($this->day_of_month),
        };
    }

    /**
     * Update the next run date after generation.
     */
    public function updateAfterGeneration(): void
    {
        $this->update([
            'next_run_date' => $this->calculateNextRunDate(),
            'last_generated_at' => now(),
            'generation_count' => $this->generation_count + 1,
        ]);
    }

    /**
     * Check if template should be marked as completed.
     */
    public function shouldComplete(): bool
    {
        return $this->end_date !== null && $this->next_run_date > $this->end_date;
    }
}
