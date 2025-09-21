<?php

namespace App\Observers;

use App\Actions\Inventory\CreateJournalEntryForStockMoveAction;
use App\Enums\Inventory\StockMoveStatus;
use App\Models\AuditLog;
use App\Models\StockMove;
use Illuminate\Support\Facades\Auth;

class StockMoveObserver
{
    public function created(StockMove $stockMove): void
    {
        $this->logAction('created', $stockMove);
    }

    public function updated(StockMove $stockMove): void
    {
        // Check if the status was just changed to 'Done'
        if ($stockMove->wasChanged('status') && $stockMove->status === StockMoveStatus::Done) {
            // Ensure a journal entry doesn't already exist to prevent duplicates
            if ($stockMove->stockMoveValuations()->doesntExist()) {
                $user = Auth::user();
                if ($user) {
                    app(CreateJournalEntryForStockMoveAction::class)->execute($stockMove, $user);
                }
            }
        }

        $this->logAction('updated', $stockMove, $stockMove->getDirty());
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
