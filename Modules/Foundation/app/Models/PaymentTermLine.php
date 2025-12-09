<?php

namespace Modules\Foundation\Models;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Eloquent;
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
 * @property \Modules\Foundation\Enums\PaymentTerms\PaymentTermType $type
 * @property int $days
 * @property float $percentage
 * @property int|null $day_of_month
 * @property float|null $discount_percentage
 * @property int|null $discount_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PaymentTerm $paymentTerm
 *
 * @method static \Modules\Foundation\Database\Factories\PaymentTermLineFactory factory($count = null, $state = [])
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
 * @mixin Eloquent
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class])]
class PaymentTermLine extends Model
{
    use HasFactory;

    protected static function newFactory(): \Modules\Foundation\Database\Factories\PaymentTermLineFactory
    {
        return \Modules\Foundation\Database\Factories\PaymentTermLineFactory::new();
    }

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
        'type' => \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::class,
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
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Net => $documentDate->copy()->addDays($this->days),
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::EndOfMonth => $this->calculateEndOfMonthDate($documentDate),
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::DayOfMonth => $this->calculateDayOfMonthDate($documentDate),
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Immediate => $documentDate->copy(),
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
    public function calculateDiscountAmount(Money $amount, Carbon $documentDate, Carbon $paymentDate): Money
    {
        if (! $this->hasEarlyPaymentDiscount($documentDate, $paymentDate)) {
            return Money::of(0, $amount->getCurrency());
        }

        $discountRate = $this->discount_percentage / 100;

        return $amount->multipliedBy($discountRate, RoundingMode::HALF_UP);
    }

    /**
     * Get a human-readable description of this payment term line.
     */
    public function getDescription(): string
    {
        $description = match ($this->type) {
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Immediate => __('foundation::payment_terms.immediate'),
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Net => __('foundation::payment_terms.net_days', ['days' => $this->days]),
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::EndOfMonth => $this->days > 0
                ? __('foundation::payment_terms.end_of_month_plus_days', ['days' => $this->days])
                : __('foundation::payment_terms.end_of_month'),
            \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::DayOfMonth => __('foundation::payment_terms.day_of_month', [
                'day' => (string) $this->day_of_month,
                'days' => (string) $this->days,
            ]),
        };

        if ($this->discount_percentage && $this->discount_days) {
            $description .= ' '.__('foundation::payment_terms.with_discount', [
                'percentage' => $this->discount_percentage,
                'days' => $this->discount_days,
            ]);
        }

        return $description;
    }
}
