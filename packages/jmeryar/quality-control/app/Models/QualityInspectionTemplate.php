<?php

namespace Jmeryar\QualityControl\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jmeryar\Foundation\Observers\AuditLogObserver;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 */
#[ObservedBy([AuditLogObserver::class])]
class QualityInspectionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<QualityInspectionParameter, static>
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(QualityInspectionParameter::class, 'template_id');
    }

    /**
     * @return HasMany<QualityControlPoint, static>
     */
    public function controlPoints(): HasMany
    {
        return $this->hasMany(QualityControlPoint::class, 'inspection_template_id');
    }

    /**
     * @return HasMany<QualityCheck, static>
     */
    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class, 'inspection_template_id');
    }

    protected static function newFactory(): \Jmeryar\QualityControl\Database\Factories\QualityInspectionTemplateFactory
    {
        return \Jmeryar\QualityControl\Database\Factories\QualityInspectionTemplateFactory::new();
    }
}
