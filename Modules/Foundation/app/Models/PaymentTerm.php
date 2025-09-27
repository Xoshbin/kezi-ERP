<?php

namespace Modules\Foundation\Models;

use App\Observers\AuditLogObserver;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Database\Factories\PaymentTermFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Class PaymentTerm
 *
 * @property int $id
 * @property int $company_id
 * @property string|array<string, string> $name
 * @property string|array<string, string>|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, PaymentTermLine> $lines
 * @property-read int|null $lines_count
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @property-read Collection<int, Partner> $customers
 * @property-read int|null $customers_count
 * @property-read Collection<int, Partner> $vendors
 * @property-read int|null $vendors_count
 *
 * @method static PaymentTermFactory factory($count = null, $state = [])
 * @method static Builder<static>|PaymentTerm newModelQuery()
 * @method static Builder<static>|PaymentTerm newQuery()
 * @method static Builder<static>|PaymentTerm query()
 * @method static Builder<static>|PaymentTerm active()
 * @method static Builder<static>|PaymentTerm whereCompanyId($value)
 * @method static Builder<static>|PaymentTerm whereCreatedAt($value)
 * @method static Builder<static>|PaymentTerm whereDescription($value)
 * @method static Builder<static>|PaymentTerm whereId($value)
 * @method static Builder<static>|PaymentTerm whereIsActive($value)
 * @method static Builder<static>|PaymentTerm whereName($value)
 * @method static Builder<static>|PaymentTerm whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class PaymentTerm extends Model
{
    /** @use HasFactory<PaymentTermFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the Company that owns the PaymentTerm.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the PaymentTermLines for this PaymentTerm.
     * These define the installment structure.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PaymentTermLine::class)->orderBy('sequence');
    }

    /**
     * Get the Invoices that use this PaymentTerm.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the VendorBills that use this PaymentTerm.
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    /**
     * Get the Partners (customers) that have this as their default payment term.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Partner::class, 'customer_payment_term_id');
    }

    /**
     * Get the Partners (vendors) that have this as their default payment term.
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Partner::class, 'vendor_payment_term_id');
    }

    /**
     * Scope a query to only include active payment terms.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate due dates for a given document date and amount.
     * Returns an array of installments with due dates and amounts.
     *
     * @return array<int, array{due_date: Carbon, amount: Money, percentage: float}>
     */
    public function calculateInstallments(Carbon $documentDate, Money $totalAmount): array
    {
        $this->loadMissing('lines');

        if ($this->lines->isEmpty()) {
            // If no lines defined, default to immediate payment
            return [
                [
                    'due_date' => $documentDate,
                    'amount' => $totalAmount,
                    'percentage' => 100.0,
                ],
            ];
        }

        $installments = [];
        $remainingAmount = $totalAmount;
        $totalPercentage = $this->lines->sum('percentage');

        foreach ($this->lines as $index => $line) {
            $dueDate = $line->calculateDueDate($documentDate);

            // For the last installment, use remaining amount to avoid rounding issues
            if ($index === $this->lines->count() - 1) {
                $amount = $remainingAmount;
            } else {
                $percentage = $line->percentage / $totalPercentage;
                $amount = $totalAmount->multipliedBy($percentage, RoundingMode::HALF_UP);
                $remainingAmount = $remainingAmount->minus($amount);
            }

            $installments[] = [
                'due_date' => $dueDate,
                'amount' => $amount,
                'percentage' => $line->percentage,
            ];
        }

        return $installments;
    }

    /**
     * Get a human-readable description of the payment terms.
     */
    public function getDescriptionAttribute(): string
    {
        $this->loadMissing('lines');

        if ($this->lines->isEmpty()) {
            return __('payment_terms.immediate_payment');
        }

        if ($this->lines->count() === 1) {
            $line = $this->lines->first();
            if ($line->percentage == 100 && $line->days == 0) {
                return __('payment_terms.immediate_payment');
            }

            return __('payment_terms.net_days', ['days' => $line->days]);
        }

        // Multiple installments
        $descriptions = $this->lines->map(function (PaymentTermLine $line) {
            return __('payment_terms.installment_description', [
                'percentage' => $line->percentage,
                'days' => $line->days,
            ]);
        });

        return $descriptions->join(', ');
    }
}
