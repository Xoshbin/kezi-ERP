<?php

namespace Modules\Accounting\Models;

use Database\Factories\FiscalPositionAccountMappingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class FiscalPositionAccountMapping
 *
 * @property int $fiscal_position_id
 * @property int $original_account_id
 * @property int $mapped_account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FiscalPosition $fiscalPosition
 * @property-read Account $mappedAccount
 * @property-read Account $originalAccount
 *
 * @method static FiscalPositionAccountMappingFactory factory($count = null, $state = [])
 * @method static Builder<static>|FiscalPositionAccountMapping newModelQuery()
 * @method static Builder<static>|FiscalPositionAccountMapping newQuery()
 * @method static Builder<static>|FiscalPositionAccountMapping query()
 * @method static Builder<static>|FiscalPositionAccountMapping whereCreatedAt($value)
 * @method static Builder<static>|FiscalPositionAccountMapping whereFiscalPositionId($value)
 * @method static Builder<static>|FiscalPositionAccountMapping whereMappedAccountId($value)
 * @method static Builder<static>|FiscalPositionAccountMapping whereOriginalAccountId($value)
 * @method static Builder<static>|FiscalPositionAccountMapping whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FiscalPositionAccountMapping extends Model
{
    /** @use HasFactory<\Database\Factories\FiscalPositionAccountMappingFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     * As per the migration sources, this model interacts with the 'fiscal_position_account_mappings' table [1-3].
     *
     * @var string
     */
    protected $table = 'fiscal_position_account_mappings';

    /**
     * The attributes that are mass assignable.
     * These fields can be filled using mass assignment [4].
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',          // Foreign key to the parent company, ensuring data integrity [2, 3].
        'fiscal_position_id',
        'original_account_id',
        'mapped_account_id',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances for easier manipulation [5].
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
    | These relationships are fundamental for linking account mapping rules
    | to their respective fiscal positions and the actual general ledger accounts.
    */

    /**
     * Get the company that this rate belongs to.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the fiscal position that this account mapping belongs to.
     * This defines the context under which the account re-mapping takes place [1, 2].
     */
    /**
     * @return BelongsTo<FiscalPosition, static>
     */
    public function fiscalPosition(): BelongsTo
    {
        // This mapping belongs to a single FiscalPosition [6].
        return $this->belongsTo(FiscalPosition::class);
    }

    /**
     * Get the original account that is being mapped.
     * This is the source account (e.g., a standard sales income account) that will be
     * replaced when the fiscal position is applied [1, 2].
     */
    /**
     * @return BelongsTo<Account, static>
     */
    public function originalAccount(): BelongsTo
    {
        // It belongs to an Account model. We explicitly define the foreign key
        // as 'original_account_id' because it deviates from Laravel's conventional naming
        // for a simple 'belongsTo' relationship to an 'Account' model [6].
        return $this->belongsTo(Account::class, 'original_account_id');
    }

    /**
     * Get the account that the original account is mapped to.
     * This is the target account (e.g., an export sales income account) that will be
     * used after the fiscal position's rule is applied [1, 2].
     */
    /**
     * @return BelongsTo<Account, static>
     */
    public function mappedAccount(): BelongsTo
    {
        // Similar to originalAccount, we explicitly define the foreign key as 'mapped_account_id' [6].
        return $this->belongsTo(Account::class, 'mapped_account_id');
    }
}
