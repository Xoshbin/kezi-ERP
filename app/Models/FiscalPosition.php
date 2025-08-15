<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Database\Factories\FiscalPositionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Class FiscalPosition
 *
 * @package App\Models
 *
 * This Eloquent model represents a fiscal position, a crucial component in an accounting system
 * designed to automatically adjust taxes and accounts based on specific criteria, such as
 * the geographic location or business type of a customer or vendor. Fiscal positions are
 * fundamental for ensuring compliance with diverse local tax regulations and automating
 * complex tax calculations in a multi-jurisdictional environment. They provide the rules
 * for adapting the standard taxes and accounts set on products or partners for a given transaction.
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $country
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, FiscalPositionAccountMapping> $accountMappings
 * @property-read int|null $account_mappings_count
 * @property-read Company $company
 * @property-read Collection<int, FiscalPositionTaxMapping> $taxMappings
 * @property-read int|null $tax_mappings_count
 * @method static FiscalPositionFactory factory($count = null, $state = [])
 * @method static Builder<static>|FiscalPosition newModelQuery()
 * @method static Builder<static>|FiscalPosition newQuery()
 * @method static Builder<static>|FiscalPosition query()
 * @method static Builder<static>|FiscalPosition whereCompanyId($value)
 * @method static Builder<static>|FiscalPosition whereCountry($value)
 * @method static Builder<static>|FiscalPosition whereCreatedAt($value)
 * @method static Builder<static>|FiscalPosition whereId($value)
 * @method static Builder<static>|FiscalPosition whereName($value)
 * @method static Builder<static>|FiscalPosition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FiscalPosition extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fiscal_positions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'country',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances.
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
    | Fiscal positions are integral to a multi-company accounting setup and
    | drive the dynamic application of taxes and general ledger accounts.
    */
    /**
     * Get the company that this fiscal position belongs to.
     * A fiscal position is typically defined within the context of a specific company.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tax mappings for the fiscal position.
     * These mappings define how original taxes are replaced or adjusted based on this fiscal position.
     *
     * @return HasMany
     */
    public function taxMappings(): HasMany
    {
        // Assumes a FiscalPositionTaxMapping model exists to represent the pivot table.
        // The 'fiscal_position_id' is the foreign key on the fiscal_position_tax_mappings table
        // that refers to this model's primary key.
        return $this->hasMany(FiscalPositionTaxMapping::class, 'fiscal_position_id');
    }

    /**
     * Get the account mappings for the fiscal position.
     * These mappings define how original accounts are replaced or adjusted based on this fiscal position.
     *
     * @return HasMany
     */
    public function accountMappings(): HasMany
    {
        // Assumes a FiscalPositionAccountMapping model exists to represent the pivot table.
        // The 'fiscal_position_id' is the foreign key on the fiscal_position_account_mappings table
        // that refers to this model's primary key.
        return $this->hasMany(FiscalPositionAccountMapping::class, 'fiscal_position_id');
    }
}
