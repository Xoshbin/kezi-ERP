<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class FiscalPositionAccountMapping
 *
 * @package App\Models
 *
 * This Eloquent model represents a specific rule within a Fiscal Position that dictates how
 * a general ledger account (Chart of Accounts) is re-mapped or transformed during a financial
 * transaction. It is a critical component for adapting accounting entries to local regulations
 * or business scenarios, ensuring that the correct accounts are impacted based on the
 * applied fiscal position.
 *
 * These mappings are essential for compliance and automation in multi-jurisdictional accounting systems,
 * allowing for dynamic adjustment of general ledger accounts (e.g., re-routing a domestic
 * sales account to an export sales account for international transactions).
 *
 * @property int $id Primary key, auto-incrementing.
 * @property int $fiscal_position_id Foreign key to the 'fiscal_positions' table, linking to the parent fiscal position [1, 2].
 * @property int $original_account_id Foreign key to the 'accounts' table, representing the account that is being re-mapped [1, 2].
 * @property int $mapped_account_id Foreign key to the 'accounts' table, representing the account that the original_account_id is re-mapped to [1, 2].
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read \App\Models\FiscalPosition $fiscalPosition The fiscal position to which this mapping belongs.
 * @property-read \App\Models\Account $originalAccount The original account being mapped.
 * @property-read \App\Models\Account $mappedAccount The account to which the original account is mapped.
 */
class FiscalPositionAccountMapping extends Model
{
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
     * @var array<int, string>
     */
    protected $fillable = [
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
     * Get the fiscal position that this account mapping belongs to.
     * This defines the context under which the account re-mapping takes place [1, 2].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mappedAccount(): BelongsTo
    {
        // Similar to originalAccount, we explicitly define the foreign key as 'mapped_account_id' [6].
        return $this->belongsTo(Account::class, 'mapped_account_id');
    }
}
