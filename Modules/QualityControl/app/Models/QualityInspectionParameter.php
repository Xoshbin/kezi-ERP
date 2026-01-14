<?php

namespace Modules\QualityControl\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\QualityControl\Enums\QualityCheckType;

/**
 * @property int $id
 * @property int $template_id
 * @property string $name
 * @property QualityCheckType $check_type
 * @property float|null $min_value
 * @property float|null $max_value
 * @property string|null $unit_of_measure
 * @property string|null $instructions
 * @property int $sequence
 */
#[ObservedBy([AuditLogObserver::class])]
class QualityInspectionParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'check_type',
        'min_value',
        'max_value',
        'unit_of_measure',
        'instructions',
        'sequence',
    ];

    protected $casts = [
        'check_type' => QualityCheckType::class,
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
        'sequence' => 'integer',
    ];

    /**
     * @return BelongsTo<QualityInspectionTemplate, static>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QualityInspectionTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<QualityCheckLine, static>
     */
    public function checkLines(): HasMany
    {
        return $this->hasMany(QualityCheckLine::class, 'parameter_id');
    }

    /**
     * Check if a numeric value is within tolerance
     */
    public function isWithinTolerance(float $value): bool
    {
        if ($this->check_type !== QualityCheckType::Measure) {
            return true;
        }

        if ($this->min_value !== null && $value < $this->min_value) {
            return false;
        }

        if ($this->max_value !== null && $value > $this->max_value) {
            return false;
        }

        return true;
    }

    protected static function newFactory(): \Modules\QualityControl\Database\Factories\QualityInspectionParameterFactory
    {
        return \Modules\QualityControl\Database\Factories\QualityInspectionParameterFactory::new();
    }
}
