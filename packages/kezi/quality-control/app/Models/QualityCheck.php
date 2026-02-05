<?php

namespace Kezi\QualityControl\Models;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\Inventory\Models\Lot;
use Kezi\Inventory\Models\SerialNumber;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityCheckStatus;

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
 * @property bool $is_blocking
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read User|null $inspectedByUser
 * @property-read \Kezi\QualityControl\Models\QualityInspectionTemplate|null $inspectionTemplate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityCheckLine> $lines
 * @property-read int|null $lines_count
 * @property-read Lot|null $lot
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityAlert> $qualityAlerts
 * @property-read int|null $quality_alerts_count
 * @property-read SerialNumber|null $serialNumber
 * @property-read Model|\Eloquent $source
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereInspectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereInspectedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereInspectionTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereIsBlocking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereLotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereSerialNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereUpdatedAt($value)
 * @method static \Kezi\QualityControl\Database\Factories\QualityCheckFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class, \Kezi\QualityControl\Observers\QualityCheckObserver::class])]
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

    protected static function newFactory(): \Kezi\QualityControl\Database\Factories\QualityCheckFactory
    {
        return \Kezi\QualityControl\Database\Factories\QualityCheckFactory::new();
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
