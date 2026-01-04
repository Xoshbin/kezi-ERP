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
 * @property int $id
 * @property int $company_id
 * @property StockPickingType $type
 * @property StockPickingState $state
 * @property int|null $partner_id
 * @property int|null $purchase_order_id
 * @property Carbon|null $scheduled_date
 * @property Carbon|null $completed_at
 * @property Carbon|null $validated_at
 * @property int|null $validated_by_user_id
 * @property string|null $reference
 * @property string|null $grn_number
 * @property string|null $origin
 * @property int|null $created_by_user_id
 * @property-read Company $company
 * @property-read Partner|null $partner
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read User|null $createdByUser
 * @property-read User|null $validatedByUser
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
        'purchase_order_id',
        'scheduled_date',
        'completed_at',
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
