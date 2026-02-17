<?php

namespace Kezi\Pos\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $type
 * @property array $features
 * @property array $settings
 * @property bool $is_active
 * @property-read \App\Models\Company $company
 */
class PosProfile extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Kezi\Pos\Database\Factories\PosProfileFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'features',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(PosResource::class);
    }
}
