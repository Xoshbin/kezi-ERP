<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\StockMove;

class StockMoveObserver
{
    public function created(StockMove $stockMove): void
    {
        $this->logAction('created', $stockMove);
    }

    public function updated(StockMove $stockMove): void
    {
        $this->logAction('updated', $stockMove, $stockMove->getDirty());
    }

    public function deleted(StockMove $stockMove): void
    {
        $this->logAction('deleted', $stockMove);
    }

    protected function logAction(string $action, StockMove $stockMove, ?array $dirty = null): void
    {
        $user = auth()->user();

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
