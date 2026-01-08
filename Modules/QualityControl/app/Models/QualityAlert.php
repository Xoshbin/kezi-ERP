<?php

namespace Modules\QualityControl\Models;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\SerialNumber;
use Modules\Product\Models\Product;
use Modules\QualityControl\Enums\QualityAlertStatus;

/**
 * @property int $id
 * @property int $company_id
 * @property string $number
 * @property int|null $quality_check_id
 * @property int $product_id
 * @property int|null $lot_id
 * @property int|null $serial_number_id
 * @property int|null $defect_type_id
 * @property QualityAlertStatus $status
 * @property string $description
 * @property string|null $root_cause
 * @property string|null $corrective_action
 * @property string|null $preventive_action
 * @property int|null $assigned_to_user_id
 * @property int $reported_by_user_id
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 */
#[ObservedBy([AuditLogObserver::class])]
class QualityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'number',
        'quality_check_id',
        'product_id',
        'lot_id',
        'serial_number_id',
        'defect_type_id',
        'status',
        'description',
        'root_cause',
        'corrective_action',
        'preventive_action',
        'assigned_to_user_id',
        'reported_by_user_id',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => QualityAlertStatus::class,
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<QualityCheck, static>
     */
    public function qualityCheck(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Lot, static>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    /**
     * @return BelongsTo<SerialNumber, static>
     */
    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }

    /**
     * @return BelongsTo<DefectType, static>
     */
    public function defectType(): BelongsTo
    {
        return $this->belongsTo(DefectType::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * Check if the alert is open (new or in progress)
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [
            QualityAlertStatus::New,
            QualityAlertStatus::InProgress,
        ]);
    }

    /**
     * Check if the alert is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === QualityAlertStatus::Resolved;
    }

    /**
     * Check if the alert is closed
     */
    public function isClosed(): bool
    {
        return $this->status === QualityAlertStatus::Closed;
    }

    /**
     * Check if corrective/preventive actions have been documented
     */
    public function hasCAPA(): bool
    {
        return ! empty($this->corrective_action) && ! empty($this->preventive_action);
    }
}
