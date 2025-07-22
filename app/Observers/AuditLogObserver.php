<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    /**
     * Handle the AuditLog "created" event.
     */
    public function created(Model $model): void
    {
        $this->logAction('record_created', $model);
    }

    /**
     * Handle the AuditLog "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->logAction('record_updated', $model);

    }

    /**
     * Handle the AuditLog "deleted" event.
     */
    public function deleted(AuditLog $auditLog): void
    {
        //
    }

    /**
     * Handle the AuditLog "restored" event.
     */
    public function restored(AuditLog $auditLog): void
    {
        //
    }

    /**
     * Handle the AuditLog "force deleted" event.
     */
    public function forceDeleted(AuditLog $auditLog): void
    {
        //
    }

    protected function logAction(string $eventType, Model $model): void
    {
        AuditLog::create([
            'user_id' => auth()->id(), // Get the currently logged-in user
            'event_type' => $eventType,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => json_encode($model->getOriginal()),
            'new_values' => json_encode($model->getChanges()),
            'ip_address' => request()->ip(),
        ]);
    }
}
