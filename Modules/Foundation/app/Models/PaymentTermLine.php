<?php

namespace Modules\Foundation\Models;

use App\Enums\PaymentTerms\PaymentTermType;
use App\Observers\AuditLogObserver;
use Database\Factories\PaymentTermLineFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class PaymentTermLine
 *
 * @property int $id
 * @property int $payment_term_id
 * @property int $sequence
 * @property PaymentTermType $type
 * @property int $days
 * @property float $percentage
 * @property int|null $day_of_month
 * @property float|null $discount_percentage
 * @property int|null $discount_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PaymentTerm $paymentTerm
 *
 * @method static PaymentTermLineFactory factory($count = null, $state = [])
 * @method static Builder<static>|PaymentTermLine newModelQuery()
 * @method static Builder<static>|PaymentTermLine newQuery()
 * @method static Builder<static>|PaymentTermLine query()
 * @method static Builder<static>|PaymentTermLine whereCreatedAt($value)
 * @method static Builder<static>|PaymentTermLine whereDayOfMonth($value)
 * @method static Builder<static>|PaymentTermLine whereDays($value)
 * @method static Builder<static>|PaymentTermLine whereDiscountDays($value)
 * @method static Builder<static>|PaymentTermLine whereDiscountPercentage($value)
 * @method static Builder<static>|PaymentTermLine whereId($value)
 * @method static Builder<static>|PaymentTermLine wherePaymentTermId($value)
 * @method static Builder<static>|PaymentTermLine wherePercentage($value)
 * @method static Builder<static>|PaymentTermLine whereSequence($value)
 * @method static Builder<static>|PaymentTermLine whereType($value)
 * @method static Builder<static>|PaymentTermLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class PaymentTermLine extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTermLineFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_term_id',
        'sequence',
        'type',
        'days',
        'percentage',
        'day_of_month',
        'discount_percentage',
        'discount_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => PaymentTermType::class,
        'percentage' => 'float',
        'discount_percentage' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the PaymentTerm that owns this line.
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * Calculate the due date based on the document date and this line's configuration.
     */
    public function calculateDueDate(Carbon $documentDate): Carbon
    {
        return match ($this->type) {
            PaymentTermType::Net => $documentDate->copy()->addDays($this->days),
            PaymentTermType::EndOfMonth => $this->calculateEndOfMonthDate($documentDate),
            PaymentTermType::DayOfMonth => $this->calculateDayOfMonthDate($documentDate),
            PaymentTermType::Immediate => $documentDate->copy(),
        };
    }

    /**
     * Calculate due date for end of month terms.
     * Example: "End of month + 30 days"
     */
    private function calculateEndOfMonthDate(Carbon $documentDate): Carbon
    {
        return $documentDate->copy()
            ->endOfMonth()
            ->addDays($this->days);
    }

    /**
     * Calculate due date for specific day of month terms.
     * Example: "15th of next month"
     */
    private function calculateDayOfMonthDate(Carbon $documentDate): Carbon
    {
        $targetDay = $this->day_of_month ?? 1;
        $dueDate = $documentDate->copy()->addDays($this->days);

        // Set to the target day of the month
        $dueDate->day($targetDay);

        // If the target day has already passed this month, move to next month
        if ($dueDate->lte($documentDate)) {
            $dueDate->addMonth();
        }

        // Handle months with fewer days (e.g., February 30th -> February 28th)
        if ($dueDate->day !== $targetDay) {
            $dueDate->endOfMonth();
        }

        return $dueDate;
    }

    /**
     * Check if early payment discount applies for a given payment date.
     */
    public function hasEarlyPaymentDiscount(Carbon $documentDate, Carbon $paymentDate): bool
    {
        if (! $this->discount_percentage || ! $this->discount_days) {
            return false;
        }

        $discountDeadline = $documentDate->copy()->addDays($this->discount_days);

        return $paymentDate->lte($discountDeadline);
    }

    /**
     * Calculate the discount amount for early payment.
     */
    public function calculateDiscountAmount(\Brick\Money\Money $amount, Carbon $documentDate, Carbon $paymentDate): \Brick\Money\Money
    {
        if (! $this->hasEarlyPaymentDiscount($documentDate, $paymentDate)) {
            return \Brick\Money\Money::of(0, $amount->getCurrency());
        }

        $discountRate = $this->discount_percentage / 100;

        return $amount->multipliedBy($discountRate, \Brick\Math\RoundingMode::HALF_UP);
    }

    /**
     * Get a human-readable description of this payment term line.
     */
    public function getDescription(): string
    {
        $description = match ($this->type) {
            PaymentTermType::Immediate => __('payment_terms.immediate'),
            PaymentTermType::Net => __('payment_terms.net_days', ['days' => $this->days]),
            PaymentTermType::EndOfMonth => $this->days > 0
                ? __('payment_terms.end_of_month_plus_days', ['days' => $this->days])
                : __('payment_terms.end_of_month'),
            PaymentTermType::DayOfMonth => __('payment_terms.day_of_month', [
                'day' => (string) $this->day_of_month,
                'days' => (string) $this->days,
            ]),
        };

        if ($this->discount_percentage && $this->discount_days) {
            $description .= ' '.__('payment_terms.with_discount', [
                'percentage' => $this->discount_percentage,
                'days' => $this->discount_days,
            ]);
        }

        return $description;
    }
}
