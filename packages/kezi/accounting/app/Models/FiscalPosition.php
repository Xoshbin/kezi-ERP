<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Class FiscalPosition
 *
 * @property int $id
 * @property int $company_id
 * @property string|array<string, string> $name
 * @property string|null $country
 * @property bool $auto_apply
 * @property bool $vat_required
 * @property string|null $zip_from
 * @property string|null $zip_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, FiscalPositionAccountMapping> $accountMappings
 * @property-read int|null $account_mappings_count
 * @property-read Company $company
 * @property-read Collection<int, FiscalPositionTaxMapping> $taxMappings
 * @property-read int|null $tax_mappings_count
 *
 * @method static Builder<static>|FiscalPosition newModelQuery()
 * @method static Builder<static>|FiscalPosition newQuery()
 * @method static Builder<static>|FiscalPosition query()
 * @method static Builder<static>|FiscalPosition whereAutoApply($value)
 * @method static Builder<static>|FiscalPosition whereCompanyId($value)
 * @method static Builder<static>|FiscalPosition whereCountry($value)
 * @method static Builder<static>|FiscalPosition whereCreatedAt($value)
 * @method static Builder<static>|FiscalPosition whereId($value)
 * @method static Builder<static>|FiscalPosition whereName($value)
 * @method static Builder<static>|FiscalPosition whereUpdatedAt($value)
 * @method static Builder<static>|FiscalPosition whereVatRequired($value)
 * @method static Builder<static>|FiscalPosition whereZipFrom($value)
 * @method static Builder<static>|FiscalPosition whereZipTo($value)
 *
 * @property-read mixed $translations
 *
 * @method static Builder<static>|FiscalPosition autoApply()
 * @method static Builder<static>|FiscalPosition whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|FiscalPosition whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|FiscalPosition whereLocale(string $column, string $locale)
 * @method static Builder<static>|FiscalPosition whereLocales(string $column, array $locales)
 * @method static \Kezi\Accounting\Database\Factories\FiscalPositionFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class FiscalPosition extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\FiscalPositionFactory
    {
        return \Kezi\Accounting\Database\Factories\FiscalPositionFactory::new();
    }

    /** @var array<int, string> */
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
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'country',
        'auto_apply',
        'vat_required',
        'zip_from',
        'zip_to',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'auto_apply' => 'boolean',
        'vat_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include fiscal positions that can be automatically applied.
     */
    public function scopeAutoApply(Builder $query): Builder
    {
        return $query->where('auto_apply', true);
    }

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
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tax mappings for the fiscal position.
     * These mappings define how original taxes are replaced or adjusted based on this fiscal position.
     */
    /**
     * @return HasMany<FiscalPositionTaxMapping, static>
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
     */
    /**
     * @return HasMany<FiscalPositionAccountMapping, static>
     */
    public function accountMappings(): HasMany
    {
        // Assumes a FiscalPositionAccountMapping model exists to represent the pivot table.
        // The 'fiscal_position_id' is the foreign key on the fiscal_position_account_mappings table
        // that refers to this model's primary key.
        return $this->hasMany(FiscalPositionAccountMapping::class, 'fiscal_position_id');
    }
}
