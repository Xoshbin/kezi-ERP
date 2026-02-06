<?php

namespace Kezi\QualityControl\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\QualityControl\Enums\QualityCheckType;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityCheckLine> $checkLines
 * @property-read int|null $check_lines_count
 * @property-read \Kezi\QualityControl\Models\QualityInspectionTemplate $template
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereCheckType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereMaxValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereMinValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereUnitOfMeasure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionParameter whereUpdatedAt($value)
 * @method static \Kezi\QualityControl\Database\Factories\QualityInspectionParameterFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
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

    protected static function newFactory(): \Kezi\QualityControl\Database\Factories\QualityInspectionParameterFactory
    {
        return \Kezi\QualityControl\Database\Factories\QualityInspectionParameterFactory::new();
    }
}
