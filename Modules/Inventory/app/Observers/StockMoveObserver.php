<?php

namespace Modules\Inventory\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\Foundation\Models\AuditLog;
use Modules\Inventory\Actions\Inventory\ProcessIncomingStockAction;
use Modules\Inventory\Actions\Inventory\ProcessOutgoingStockAction;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Events\Inventory\StockMoveConfirmed;
use Modules\Inventory\Models\StockMove;
use Modules\Purchase\Models\VendorBill;

class StockMoveObserver
{
    public function creating(StockMove $stockMove): void
    {
        if (empty($stockMove->reference)) {
            $stockMove->reference = 'SM-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        }
    }

    public function created(StockMove $stockMove): void
    {
        if ($stockMove->status === StockMoveStatus::Done) {
            $this->handleStockMoveConfirmation($stockMove);
        }
        $this->logAction('created', $stockMove);
    }

    public function updated(StockMove $stockMove): void
    {
        \Illuminate\Support\Facades\Log::info("Observer updated: status={$stockMove->status->value}, wasChanged=".($stockMove->wasChanged('status') ? 'yes' : 'no').', dirty='.json_encode($stockMove->getDirty()));
        // Check if the status was just changed to 'Done'
        if ($stockMove->wasChanged('status') && $stockMove->status === StockMoveStatus::Done) {
            $this->handleStockMoveConfirmation($stockMove);
        }

        $this->logAction('updated', $stockMove, $stockMove->getDirty());
    }

    /**
     * Handle logic when a stock move is confirmed (status becomes Done).
     */
    protected function handleStockMoveConfirmation(StockMove $stockMove): void
    {
        // Skip auto-created moves linked to Vendor Bills (handled manually and consolidated in listeners)
        if ($stockMove->source_type === VendorBill::class) {
            return;
        }

        // Skip adjustments - they have custom accounting logic in CreateInventoryAdjustmentAction
        if ($stockMove->move_type === StockMoveType::Adjustment) {
            return;
        }

        // Process valuation and side effects synchronously to ensure atomic transactions and propagate exceptions
        if ($stockMove->move_type === StockMoveType::Incoming) {
            app(ProcessIncomingStockAction::class)->execute($stockMove);
        } elseif ($stockMove->move_type === StockMoveType::Outgoing) {
            app(ProcessOutgoingStockAction::class)->execute($stockMove);
        }

        // Still dispatch event for other modules to listen (non-core side effects)
        StockMoveConfirmed::dispatch($stockMove);
    }

    public function deleted(StockMove $stockMove): void
    {
        $this->logAction('deleted', $stockMove);
    }

    /**
     * @param  array<string, mixed>|null  $dirty
     */
    protected function logAction(string $action, StockMove $stockMove, ?array $dirty = null): void
    {
        $user = Auth::user();

        // Skip audit logging if no authenticated user (e.g., in console/tinker context)
        if (! $user) {
            return;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_id' => $stockMove->id,
            'auditable_type' => StockMove::class,
            'event_type' => $action,
            'old_values' => $action === 'updated' ? $stockMove->getOriginal() : null,
            'new_values' => $action !== 'deleted' ? ($dirty ?? $stockMove->toArray()) : null,
            'description' => "Stock move {$action}: {$stockMove->id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
