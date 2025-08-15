<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        $this->logAction('record_created', $model);
    }

    public function updated(Model $model): void
    {
        // Check if the 'status' attribute was one of the fields that changed.
        $eventType = $model->wasChanged('status') ? 'status_changed' : 'record_updated';

        $this->logAction($eventType, $model);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        // Prevent logging the deletion of an audit log itself.
        if ($model instanceof AuditLog) {
            return;
        }
        $this->logAction('record_deleted', $model);
    }

    protected function logAction(string $eventType, Model $model): void
    {
        if (app()->runningInConsole() && ! auth()->check()) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        if ($eventType === 'record_deleted') {
            // For deletions, all original attributes are the "old values".
            $oldValues = $model->getAttributes();
        } elseif ($eventType !== 'record_created') {
            foreach ($model->getChanges() as $key => $value) {
                if ($key === 'updated_at') continue;
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $value;
            }

            if (empty($newValues)) return;
        }

        // Determine company_id from Filament tenant or model
        $companyId = null;
        if (class_exists(\Filament\Facades\Filament::class)) {
            $tenant = \Filament\Facades\Filament::getTenant();
            if ($tenant) {
                $companyId = $tenant->id;
            }
        }

        // If no tenant, try to get company from the model being audited
        if (!$companyId && method_exists($model, 'company') && $model->company) {
            $companyId = $model->company->id;
        } elseif (!$companyId && isset($model->company_id)) {
            $companyId = $model->company_id;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'company_id' => $companyId,
            'event_type' => $eventType,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }
}
