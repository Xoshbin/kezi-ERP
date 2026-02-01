<?php

namespace Jmeryar\QualityControl\Models;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Jmeryar\Foundation\Observers\AuditLogObserver;
use Jmeryar\Inventory\Models\Lot;
use Jmeryar\Inventory\Models\SerialNumber;
use Jmeryar\Product\Models\Product;
use Jmeryar\QualityControl\Enums\QualityCheckStatus;

/**
 * @property int $id
 * @property int $company_id
 * @property string $number
 * @property string $source_type
 * @property int $source_id
 * @property int $product_id
 * @property int|null $lot_id
 * @property int|null $serial_number_id
 * @property int|null $inspection_template_id
 * @property QualityCheckStatus $status
 * @property int|null $inspected_by_user_id
 * @property Carbon|null $inspected_at
 * @property string|null $notes
 */
#[ObservedBy([AuditLogObserver::class, \Jmeryar\QualityControl\Observers\QualityCheckObserver::class])]
class QualityCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'number',
        'source_type',
        'source_id',
        'product_id',
        'lot_id',
        'serial_number_id',
        'inspection_template_id',
        'status',
        'inspected_by_user_id',
        'inspected_at',
        'notes',
        'is_blocking',
    ];

    protected $casts = [
        'status' => QualityCheckStatus::class,
        'inspected_at' => 'datetime',
        'is_blocking' => 'boolean',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
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
     * @return BelongsTo<QualityInspectionTemplate, static>
     */
    public function inspectionTemplate(): BelongsTo
    {
        return $this->belongsTo(QualityInspectionTemplate::class, 'inspection_template_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function inspectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by_user_id');
    }

    /**
     * @return HasMany<QualityCheckLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(QualityCheckLine::class);
    }

    protected static function newFactory(): \Jmeryar\QualityControl\Database\Factories\QualityCheckFactory
    {
        return \Jmeryar\QualityControl\Database\Factories\QualityCheckFactory::new();
    }

    /**
     * @return HasMany<QualityAlert, static>
     */
    public function qualityAlerts(): HasMany
    {
        return $this->hasMany(QualityAlert::class);
    }

    /**
     * Check if the inspection passed
     */
    public function isPassed(): bool
    {
        return $this->status === QualityCheckStatus::Passed;
    }

    /**
     * Check if the inspection failed
     */
    public function isFailed(): bool
    {
        return $this->status === QualityCheckStatus::Failed;
    }

    /**
     * Check if the inspection is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            QualityCheckStatus::Draft,
            QualityCheckStatus::InProgress,
        ]);
    }

    /**
     * Check if all check lines passed
     */
    public function areAllLinesPassed(): bool
    {
        if ($this->lines->isEmpty()) {
            return false;
        }

        return $this->lines->every(function (QualityCheckLine $line) {
            return $line->isPassed();
        });
    }

    /**
     * Get count of failed check lines
     */
    public function getFailedLinesCount(): int
    {
        return $this->lines->filter(function (QualityCheckLine $line) {
            return $line->isFailed();
        })->count();
    }
}
