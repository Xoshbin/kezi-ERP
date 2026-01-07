<?php

namespace Modules\Inventory\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Purchase\Models\PurchaseOrder;

/**
 * StockPicking Model - Represents goods movement documents (receipts, deliveries, internal transfers).
 *
 * For the GRN (Goods Receipt Note) workflow, this model serves as the receiving document
 * that tracks goods received against a Purchase Order.
 *
 * For Internal Transfers (two-step workflow):
 * - Step 1 (Ship): Source → Transit location
 * - Step 2 (Receive): Transit → Destination location
 *
 * @property int $id
 * @property int $company_id
 * @property StockPickingType $type
 * @property StockPickingState $state
 * @property int|null $partner_id
 * @property int|null $transit_location_id
 * @property int|null $destination_location_id
 * @property int|null $purchase_order_id
 * @property Carbon|null $scheduled_date
 * @property Carbon|null $completed_at
 * @property Carbon|null $shipped_at
 * @property int|null $shipped_by_user_id
 * @property Carbon|null $received_at
 * @property int|null $received_by_user_id
 * @property Carbon|null $validated_at
 * @property int|null $validated_by_user_id
 * @property string|null $reference
 * @property string|null $grn_number
 * @property string|null $origin
 * @property int|null $created_by_user_id
 * @property-read Company $company
 * @property-read Partner|null $partner
 * @property-read StockLocation|null $transitLocation
 * @property-read StockLocation|null $destinationLocation
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read User|null $createdByUser
 * @property-read User|null $validatedByUser
 * @property-read User|null $shippedByUser
 * @property-read User|null $receivedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMove> $stockMoves
 */
#[ObservedBy([AuditLogObserver::class])]
class StockPicking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'state',
        'partner_id',
        'transit_location_id',
        'destination_location_id',
        'purchase_order_id',
        'scheduled_date',
        'completed_at',
        'shipped_at',
        'shipped_by_user_id',
        'received_at',
        'received_by_user_id',
        'validated_at',
        'validated_by_user_id',
        'reference',
        'grn_number',
        'origin',
        'created_by_user_id',
    ];

    protected $casts = [
        'type' => StockPickingType::class,
        'state' => StockPickingState::class,
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Partner, static>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the Purchase Order associated with this picking (for GRN workflow).
     *
     * @return BelongsTo<PurchaseOrder, static>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    /**
     * @return HasMany<StockMove, static>
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class, 'picking_id');
    }

    /**
     * Get the transit location for internal transfers.
     *
     * @return BelongsTo<StockLocation, static>
     */
    public function transitLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'transit_location_id');
    }

    /**
     * Get the destination location for internal transfers.
     *
     * @return BelongsTo<StockLocation, static>
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'destination_location_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function shippedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by_user_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    /**
     * Check if this picking is a Goods Receipt (incoming from vendor).
     */
    public function isGoodsReceipt(): bool
    {
        return $this->type === StockPickingType::Receipt;
    }

    /**
     * Check if this picking is linked to a Purchase Order.
     */
    public function isLinkedToPurchaseOrder(): bool
    {
        return $this->purchase_order_id !== null;
    }

    /**
     * Check if this picking can be validated.
     */
    public function canBeValidated(): bool
    {
        return in_array($this->state, [
            StockPickingState::Confirmed,
            StockPickingState::Assigned,
        ], true);
    }

    /**
     * Check if this picking is in draft state.
     */
    public function isDraft(): bool
    {
        return $this->state === StockPickingState::Draft;
    }

    /**
     * Check if this picking is done (validated).
     */
    public function isDone(): bool
    {
        return $this->state === StockPickingState::Done;
    }

    /**
     * Check if this picking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->state === StockPickingState::Cancelled;
    }

    /**
     * Check if this picking is an internal transfer.
     */
    public function isInternalTransfer(): bool
    {
        return $this->type === StockPickingType::Internal;
    }

    /**
     * Check if this transfer has been shipped (goods in transit).
     */
    public function isShipped(): bool
    {
        return $this->state === StockPickingState::Shipped;
    }

    /**
     * Check if this picking can be shipped (for internal transfers).
     */
    public function canBeShipped(): bool
    {
        return $this->isInternalTransfer()
            && in_array($this->state, [
                StockPickingState::Confirmed,
                StockPickingState::Assigned,
            ], true);
    }

    /**
     * Check if this picking can be received (for internal transfers).
     */
    public function canBeReceived(): bool
    {
        return $this->isInternalTransfer()
            && $this->state === StockPickingState::Shipped;
    }

    /**
     * Get the total quantity planned across all stock moves.
     */
    public function getTotalPlannedQuantity(): float
    {
        return $this->stockMoves->sum(function (StockMove $move) {
            return $move->productLines->sum('quantity');
        });
    }

    /**
     * Check if all stock moves are done.
     */
    public function areAllMovesDone(): bool
    {
        return $this->stockMoves->every(fn (StockMove $move) => $move->status->value === 'done');
    }

    protected static function newFactory(): \Modules\Inventory\Database\Factories\StockPickingFactory
    {
        return \Modules\Inventory\Database\Factories\StockPickingFactory::new();
    }
}
