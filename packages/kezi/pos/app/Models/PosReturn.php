<?php

namespace Kezi\Pos\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Models\StockMove;
use Kezi\Payment\Models\Payment;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Sales\Models\Invoice;

/**
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property int $pos_session_id
 * @property int $original_order_id
 * @property int $currency_id
 * @property string $return_number
 * @property \Illuminate\Support\Carbon $return_date
 * @property PosReturnStatus $status
 * @property string|null $return_reason
 * @property string|null $return_notes
 * @property int $requested_by_user_id
 * @property int|null $approved_by_user_id
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Brick\Money\Money $refund_amount
 * @property \Brick\Money\Money $restocking_fee
 * @property string|null $refund_method
 * @property int|null $credit_note_id
 * @property int|null $payment_reversal_id
 * @property int|null $stock_move_id
 * @property-read PosSession|null $session
 * @property-read PosOrder|null $originalOrder
 */
class PosReturn extends Model
{
    /** @use HasFactory<\Kezi\Pos\Database\Factories\PosReturnFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): \Kezi\Pos\Database\Factories\PosReturnFactory
    {
        return \Kezi\Pos\Database\Factories\PosReturnFactory::new();
    }

    protected $fillable = [
        'uuid',
        'company_id',
        'pos_session_id',
        'original_order_id',
        'currency_id',
        'return_number',
        'return_date',
        'status',
        'return_reason',
        'return_notes',
        'requested_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'refund_amount',
        'restocking_fee',
        'refund_method',
        'credit_note_id',
        'payment_reversal_id',
        'stock_move_id',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'approved_at' => 'datetime',
            'status' => PosReturnStatus::class,
            'refund_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
            'restocking_fee' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function originalOrder(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'original_order_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosReturnLine::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'credit_note_id');
    }

    public function paymentReversal(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_reversal_id');
    }

    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }

    // Helper methods
    public function isApproved(): bool
    {
        return $this->status === PosReturnStatus::Approved;
    }

    public function canBeApproved(): bool
    {
        return $this->status === PosReturnStatus::PendingApproval;
    }

    public function requiresApproval(): bool
    {
        // Check if approval is required based on return policy
        $session = $this->session;
        if (! $session) {
            return false;
        }

        $profile = $session->profile;
        if (! $profile) {
            return false;
        }

        $returnPolicy = $profile->return_policy ?? [];

        if (! ($returnPolicy['require_manager_approval'] ?? false)) {
            return false;
        }

        $threshold = $returnPolicy['manager_approval_threshold'] ?? 0;

        return $this->refund_amount->getMinorAmount()->toInt() >= $threshold;
    }
}
