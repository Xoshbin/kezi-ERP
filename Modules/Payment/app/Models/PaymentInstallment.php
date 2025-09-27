<?php

namespace Modules\Payment\Models;

use App\Casts\DocumentCurrencyMoneyCast;
use App\Enums\PaymentInstallments\InstallmentStatus;
use App\Observers\AuditLogObserver;
use Brick\Money\Money;
use Database\Factories\PaymentInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class PaymentInstallment
 *
 * @property int $id
 * @property int $company_id
 * @property string $installment_type
 * @property int $installment_id
 * @property int $sequence
 * @property Carbon $due_date
 * @property Money $amount
 * @property Money $paid_amount
 * @property InstallmentStatus $status
 * @property float|null $discount_percentage
 * @property Carbon|null $discount_deadline
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Model $installmentable
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 *
 * @method static PaymentInstallmentFactory factory($count = null, $state = [])
 * @method static Builder<static>|PaymentInstallment newModelQuery()
 * @method static Builder<static>|PaymentInstallment newQuery()
 * @method static Builder<static>|PaymentInstallment query()
 * @method static Builder<static>|PaymentInstallment whereAmount($value)
 * @method static Builder<static>|PaymentInstallment whereCompanyId($value)
 * @method static Builder<static>|PaymentInstallment whereCreatedAt($value)
 * @method static Builder<static>|PaymentInstallment whereDiscountDeadline($value)
 * @method static Builder<static>|PaymentInstallment whereDiscountPercentage($value)
 * @method static Builder<static>|PaymentInstallment whereDueDate($value)
 * @method static Builder<static>|PaymentInstallment whereId($value)
 * @method static Builder<static>|PaymentInstallment whereInstallmentId($value)
 * @method static Builder<static>|PaymentInstallment whereInstallmentType($value)
 * @method static Builder<static>|PaymentInstallment wherePaidAmount($value)
 * @method static Builder<static>|PaymentInstallment whereSequence($value)
 * @method static Builder<static>|PaymentInstallment whereStatus($value)
 * @method static Builder<static>|PaymentInstallment whereUpdatedAt($value)
 * @method static Builder<static>|PaymentInstallment overdue()
 * @method static Builder<static>|PaymentInstallment dueSoon(int $days = 7)
 * @method static Builder<static>|PaymentInstallment unpaid()
 *
 * @mixin \Eloquent
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class])]
class PaymentInstallment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentInstallmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'installment_type',
        'installment_id',
        'sequence',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'discount_percentage',
        'discount_deadline',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'paid_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'status' => InstallmentStatus::class,
        'discount_percentage' => 'float',
        'discount_deadline' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'paid_amount' => 0,
    ];

    /**
     * Get the Company that owns this installment.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent model (Invoice or VendorBill).
     */
    public function installmentable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('installmentable', 'installment_type', 'installment_id');
    }

    /**
     * Get the Payments that have been applied to this installment.
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_installment_links')
            ->withPivot('amount_applied')
            ->withTimestamps();
    }

    /**
     * Scope to get overdue installments.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', InstallmentStatus::Paid);
    }

    /**
     * Scope to get installments due soon.
     */
    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
            ->where('status', '!=', InstallmentStatus::Paid);
    }

    /**
     * Scope to get unpaid installments.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', '!=', InstallmentStatus::Paid);
    }

    /**
     * Get the remaining amount to be paid.
     */
    public function getRemainingAmount(): Money
    {
        return $this->amount->minus($this->paid_amount);
    }

    /**
     * Check if this installment is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->paid_amount->isGreaterThanOrEqualTo($this->amount);
    }

    /**
     * Check if this installment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && ! $this->isFullyPaid();
    }

    /**
     * Check if early payment discount is available.
     */
    public function hasEarlyPaymentDiscount(): bool
    {
        return $this->discount_percentage > 0
            && $this->discount_deadline
            && $this->discount_deadline->isFuture();
    }

    /**
     * Calculate the discount amount for early payment.
     */
    public function calculateDiscountAmount(): Money
    {
        if (! $this->hasEarlyPaymentDiscount()) {
            return Money::of(0, $this->amount->getCurrency());
        }

        $discountRate = $this->discount_percentage / 100;
        $remainingAmount = $this->getRemainingAmount();

        return $remainingAmount->multipliedBy($discountRate, \Brick\Math\RoundingMode::HALF_UP);
    }

    /**
     * Apply a payment to this installment.
     */
    public function applyPayment(Money $paymentAmount): void
    {
        $newPaidAmount = $this->paid_amount->plus($paymentAmount);

        // Ensure we don't overpay
        if ($newPaidAmount->isGreaterThan($this->amount)) {
            $newPaidAmount = $this->amount;
        }

        $this->paid_amount = $newPaidAmount;

        // Update status
        if ($this->isFullyPaid()) {
            $this->status = InstallmentStatus::Paid;
        } elseif ($this->paid_amount->isGreaterThan(Money::of(0, $this->amount->getCurrency()))) {
            $this->status = InstallmentStatus::PartiallyPaid;
        }

        $this->save();
    }

    /**
     * Get days until due date (negative if overdue).
     */
    public function getDaysUntilDue(): int
    {
        return (int) now()->diffInDays($this->due_date, false);
    }

    /**
     * Get a human-readable status description.
     */
    public function getStatusDescription(): string
    {
        if ($this->isOverdue()) {
            $daysOverdue = abs($this->getDaysUntilDue());

            return __('payment_installments.overdue_by_days', ['days' => $daysOverdue]);
        }

        if ($this->isFullyPaid()) {
            return __('payment_installments.paid');
        }

        $daysUntilDue = $this->getDaysUntilDue();
        if ($daysUntilDue === 0) {
            return __('payment_installments.due_today');
        }

        return __('payment_installments.due_in_days', ['days' => $daysUntilDue]);
    }
}
