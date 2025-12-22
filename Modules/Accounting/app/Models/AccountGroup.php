<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Enums\Accounting\RootAccountType;
use Spatie\Translatable\HasTranslations;

/**
 * Class AccountGroup
 *
 * Groups accounts together using code prefix ranges (Odoo-style approach).
 * Accounts are auto-assigned to groups based on their code patterns.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $parent_id
 * @property string $code_prefix_start
 * @property string $code_prefix_end
 * @property string|array<string, string> $name
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read AccountGroup|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AccountGroup> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 * @property-read RootAccountType $root_type
 */
class AccountGroup extends Model
{
    use HasFactory;
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected $table = 'account_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'parent_id',
        'code_prefix_start',
        'code_prefix_end',
        'name',
        'level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
    ];

    protected static function newFactory(): \Modules\Accounting\Database\Factories\AccountGroupFactory
    {
        return \Modules\Accounting\Database\Factories\AccountGroupFactory::new();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the company that owns this account group.
     *
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent group.
     *
     * @return BelongsTo<AccountGroup, static>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'parent_id');
    }

    /**
     * Get the child groups.
     *
     * @return HasMany<AccountGroup, static>
     */
    public function children(): HasMany
    {
        return $this->hasMany(AccountGroup::class, 'parent_id');
    }

    /**
     * Get the accounts belonging to this group.
     *
     * @return HasMany<Account, static>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get root type based on code prefix (GAAP standard).
     */
    public function getRootTypeAttribute(): RootAccountType
    {
        $firstDigit = substr($this->code_prefix_start, 0, 1);

        return match ($firstDigit) {
            '1' => RootAccountType::Asset,
            '2' => RootAccountType::Liability,
            '3' => RootAccountType::Equity,
            '4' => RootAccountType::Income,
            '5', '6', '7' => RootAccountType::Expense,
            default => RootAccountType::Expense,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if an account code belongs to this group's range.
     */
    public function containsCode(string $code): bool
    {
        return $code >= $this->code_prefix_start
            && $code <= $this->code_prefix_end;
    }

    /**
     * Check if this group has any accounts with transactions.
     */
    public function hasTransactions(): bool
    {
        return $this->accounts()
            ->whereHas('journalEntryLines')
            ->exists();
    }
}
