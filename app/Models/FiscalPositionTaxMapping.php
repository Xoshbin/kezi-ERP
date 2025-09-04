<?php

namespace App\Models;

use Database\Factories\FiscalPositionTaxMappingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class FiscalPositionTaxMapping
 *
 * @property int $fiscal_position_id
 * @property int $original_tax_id
 * @property int $mapped_tax_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FiscalPosition $fiscalPosition
 * @property-read Tax $mappedTax
 * @property-read Tax $originalTax
 *
 * @method static FiscalPositionTaxMappingFactory factory($count = null, $state = [])
 * @method static Builder<static>|FiscalPositionTaxMapping newModelQuery()
 * @method static Builder<static>|FiscalPositionTaxMapping newQuery()
 * @method static Builder<static>|FiscalPositionTaxMapping query()
 * @method static Builder<static>|FiscalPositionTaxMapping whereCreatedAt($value)
 * @method static Builder<static>|FiscalPositionTaxMapping whereFiscalPositionId($value)
 * @method static Builder<static>|FiscalPositionTaxMapping whereMappedTaxId($value)
 * @method static Builder<static>|FiscalPositionTaxMapping whereOriginalTaxId($value)
 * @method static Builder<static>|FiscalPositionTaxMapping whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FiscalPositionTaxMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * As indicated in the migration script, this model interacts with the 'fiscal_position_tax_mappings' table.
     *
     * @var string
     */
    protected $table = 'fiscal_position_tax_mappings';

    /**
     * The attributes that are mass assignable.
     * These fields can be safely filled via mass assignment, reflecting the core data
     * necessary for a tax mapping rule.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',             // Foreign key to the parent company, ensuring data integrity [2, 3].
        'fiscal_position_id',
        'original_tax_id',
        'mapped_tax_id',
    ];

    /**
     * The attributes that should be cast.
     * Ensures that 'created_at' and 'updated_at' are Carbon instances for consistent
     * date and time manipulation.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | These relationships are fundamental for linking tax mapping rules
    | to their respective fiscal positions and the actual tax definitions.
    */

    /**
     * Get the company that this rate belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the fiscal position that this tax mapping belongs to.
     * This defines the overarching context or set of rules under which the
     * tax re-mapping is applied [1, 2].
     */
    public function fiscalPosition(): BelongsTo
    {
        // A FiscalPositionTaxMapping record is intrinsically tied to one FiscalPosition.
        return $this->belongsTo(FiscalPosition::class);
    }

    /**
     * Get the original tax that is subject to re-mapping.
     * This represents the default tax rate or rule that would typically be applied
     * before any fiscal position adjustments [1, 3].
     */
    public function originalTax(): BelongsTo
    {
        // Explicitly defining the foreign key 'original_tax_id' ensures correct
        // linkage to the 'taxes' table, as it deviates from Laravel's default
        // naming convention for a 'belongsTo' relationship to a 'Tax' model.
        return $this->belongsTo(Tax::class, 'original_tax_id');
    }

    /**
     * Get the tax to which the original tax is re-mapped.
     * This is the effective tax rate or rule that will be applied once the
     * fiscal position's mapping rule is triggered [1, 3].
     */
    public function mappedTax(): BelongsTo
    {
        // Similar to 'originalTax', the foreign key 'mapped_tax_id' is explicitly
        // specified to establish the correct relationship to the 'taxes' table.
        return $this->belongsTo(Tax::class, 'mapped_tax_id');
    }
}
