<?php

namespace Modules\Purchase\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Models\StockLocation;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;

/**
 * Purchase Order Model
 *
 * Represents a purchase order - the central document for procurement as described
 * in the inventory plan. Purchase orders serve as the "single source of truth"
 * for pricing and are used for cost determination when goods are received.
 *
 * @property int $id
 * @property int $company_id
 * @property int $vendor_id
 * @property int $currency_id
 * @property int $created_by_user_id
 * @property string|null $po_number
 * @property PurchaseOrderStatus $status
 * @property string|null $reference
 * @property Carbon $po_date
 * @property Carbon|null $expected_delivery_date
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property float|null $exchange_rate_at_creation
 * @property Money $total_amount
 * @property Money $total_tax
 * @property Money|null $total_amount_company_currency
 * @property Money|null $total_tax_company_currency
 * @property string|null $notes
 * @property string|null $terms_and_conditions
 * @property int|null $delivery_location_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Partner $vendor
 * @property-read Currency $currency
 * @property-read User $createdByUser
 * @property-read StockLocation|null $deliveryLocation
 * @property-read Collection<int, PurchaseOrderLine> $lines
 * @property-read int|null $lines_count
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class])]
class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'vendor_id',
        'currency_id',
        'created_by_user_id',
        'po_number',
        'status',
        'reference',
        'po_date',
        'expected_delivery_date',
        'confirmed_at',
        'cancelled_at',
        'exchange_rate_at_creation',
        'total_amount',
        'total_tax',
        'total_amount_company_currency',
        'total_tax_company_currency',
        'notes',
        'terms_and_conditions',
        'delivery_location_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'exchange_rate_at_creation' => 'decimal:6',
        'total_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_tax' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_amount_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'total_tax_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function booted(): void
    {
        static::saving(function (self $purchaseOrder) {
            if ($purchaseOrder->relationLoaded('lines')) {
                $purchaseOrder->calculateTotalsFromLines();
            }
        });
    }

    /**
     * Get the Company that owns this Purchase Order.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Vendor (Partner) for this Purchase Order.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    /**
     * Get the Currency for this Purchase Order.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the User who created this Purchase Order.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the delivery location for this Purchase Order.
     */
    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'delivery_location_id');
    }

    /**
     * Get the Purchase Order Lines for this Purchase Order.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * Get the Vendor Bills associated with this Purchase Order.
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    /**
     * Calculate and update totals from the purchase order lines.
     */
    public function calculateTotalsFromLines(): void
    {
        if (! $this->relationLoaded('lines')) {
            $this->load('lines');
        }

        $currency = $this->currency;
        $totalAmount = Money::of(0, $currency->code);
        $totalTax = Money::of(0, $currency->code);

        foreach ($this->lines as $line) {
            $totalAmount = $totalAmount->plus($line->total);
            $totalTax = $totalTax->plus($line->total_line_tax);
        }

        $this->total_amount = $totalAmount;
        $this->total_tax = $totalTax;
    }

    /**
     * Check if the purchase order can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status->canBeEdited();
    }

    /**
     * Check if the purchase order can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this->status->canBeConfirmed();
    }

    /**
     * Check if the purchase order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Check if goods can be received against this purchase order.
     */
    public function canReceiveGoods(): bool
    {
        return $this->status->canReceiveGoods();
    }

    /**
     * Check if vendor bills can be created against this purchase order.
     */
    public function canCreateBill(): bool
    {
        // First check if status allows bill creation
        if (! $this->status->canCreateBill()) {
            return false;
        }

        // Then check if bills already exist for this PO
        return ! $this->hasBills();
    }

    /**
     * Check if this purchase order has any vendor bills.
     */
    public function hasBills(): bool
    {
        return $this->vendorBills()->exists();
    }

    /**
     * Get the count of vendor bills for this purchase order.
     */
    public function getBillsCount(): int
    {
        return $this->vendorBills()->count();
    }

    /**
     * Get the total quantity ordered across all lines.
     */
    public function getTotalQuantityOrdered(): float
    {
        return $this->lines->sum('quantity');
    }

    /**
     * Get the total quantity received across all lines.
     */
    public function getTotalQuantityReceived(): float
    {
        return $this->lines->sum('quantity_received');
    }

    /**
     * Check if the purchase order is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->getTotalQuantityOrdered() <= $this->getTotalQuantityReceived();
    }

    /**
     * Check if the purchase order is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        $received = $this->getTotalQuantityReceived();

        return $received > 0 && $received < $this->getTotalQuantityOrdered();
    }

    /**
     * Update the status based on received quantities.
     * This method should only be called from inventory/warehouse operations.
     *
     * @param  bool  $fromInventoryOperation  Whether this is called from a legitimate inventory operation
     */
    public function updateStatusBasedOnReceipts(bool $fromInventoryOperation = false): void
    {
        if (
            $this->status === PurchaseOrderStatus::Cancelled ||
            $this->status === PurchaseOrderStatus::Done
        ) {
            return; // Don't change final statuses
        }

        // Only allow receive status updates from inventory operations
        if (! $fromInventoryOperation) {
            // If not from inventory operation, only allow transition to ToReceive
            // but not to PartiallyReceived or FullyReceived
            if ($this->status->isCommitted() && $this->getTotalQuantityReceived() === 0.0) {
                $this->status = PurchaseOrderStatus::ToReceive;
            }

            return;
        }

        // Full logic only for inventory operations
        if ($this->isFullyReceived()) {
            // If fully received, move to billing phase
            $this->status = PurchaseOrderStatus::ToBill;
        } elseif ($this->isPartiallyReceived()) {
            $this->status = PurchaseOrderStatus::PartiallyReceived;
        } elseif ($this->status->isCommitted()) {
            // If committed but nothing received yet
            $this->status = PurchaseOrderStatus::ToReceive;
        }
    }

    /**
     * Update the status based on billing progress.
     */
    public function updateStatusBasedOnBilling(): void
    {
        if (
            $this->status === PurchaseOrderStatus::Cancelled ||
            $this->status === PurchaseOrderStatus::Done
        ) {
            return; // Don't change final statuses
        }

        $billsCount = $this->getBillsCount();

        if ($billsCount === 0) {
            // No bills exist - status should remain as is
            return;
        } elseif ($billsCount === 1) {
            // First bill created - move to PartiallyBilled
            $this->status = PurchaseOrderStatus::PartiallyBilled;
        } else {
            // Multiple bills exist - could be PartiallyBilled or FullyBilled
            // For now, we'll keep it as PartiallyBilled
            // In the future, this could be enhanced to check if total billed amount
            // equals total PO amount to determine FullyBilled status
            $this->status = PurchaseOrderStatus::PartiallyBilled;
        }

        $this->save();
    }

    /**
     * Mark the purchase order as done/closed.
     */
    public function markAsDone(): void
    {
        if ($this->status === PurchaseOrderStatus::FullyBilled) {
            $this->status = PurchaseOrderStatus::Done;
            $this->save();
        }
    }

    protected static function newFactory(): \Modules\Purchase\Database\Factories\PurchaseOrderFactory
    {
        return \Modules\Purchase\Database\Factories\PurchaseOrderFactory::new();
    }
}
