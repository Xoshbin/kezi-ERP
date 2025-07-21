<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 *
 * @property int $id Primary key, auto-incrementing.
 * @property int $company_id Foreign key to the 'companies' table, linking to the company this fiscal position belongs to.
 * @property string $name The name of the fiscal position (e.g., 'Domestic Customer', 'Export Customer').
 * @property string|null $country The country code (e.g., 'IQ' for Iraq) this fiscal position applies to, if specific. Nullable to allow for broader applicability.
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read \App\Models\Company $company The company to which this fiscal position belongs.
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\FiscalPositionTaxMapping> $taxMappings The tax mappings associated with this fiscal position.
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\FiscalPositionAccountMapping> $accountMappings The account mappings associated with this fiscal position.
 */
class FiscalPosition extends Model
{
    use HasFactory;

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tax mappings for the fiscal position.
     * These mappings define how original taxes are replaced or adjusted based on this fiscal position.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accountMappings(): HasMany
    {
        // Assumes a FiscalPositionAccountMapping model exists to represent the pivot table.
        // The 'fiscal_position_id' is the foreign key on the fiscal_position_account_mappings table
        // that refers to this model's primary key.
        return $this->hasMany(FiscalPositionAccountMapping::class, 'fiscal_position_id');
    }
}
