<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * @property int $id
 * @property int $company_id
 * @property int $income_account_id
 * @property int $expense_account_id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property float $unit_price
 * @property string $type
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Account $expenseAccount
 * @property-read \App\Models\Account $incomeAccount
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product bySku($sku, $companyId)
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIncomeAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products'; // Laravel's convention is 'products', so explicit declaration is optional but good for clarity.

    /**
     * The attributes that are mass assignable.
     * These fields are typically populated via user input or automated processes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'sku',
        'description',
        'unit_price',
        'type',
        'income_account_id',
        'expense_account_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     * This ensures proper data types are used when interacting with the model attributes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => MoneyCast::class, // For monetary values, decimal casting ensures precision [1].
        'is_active' => 'boolean', // Ensures boolean handling for the active status [2].
        'created_at' => 'datetime', // Laravel automatically casts these, but explicit casting can be good practice [3].
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime', // Essential for SoftDeletes [3].
    ];

    public const TYPE_SERVICE = 'service';
    public const TYPE_STORABLE_PRODUCT = 'storable product';

    public static function getTypes(): array
    {
        return [
            self::TYPE_SERVICE => 'Service',
            self::TYPE_STORABLE_PRODUCT => 'Storable Product',
        ];
    }

    /**
     * Get the Company that owns the Product.
     * This relationship is fundamental in a multi-company accounting setup, ensuring products are scoped to specific entities [4].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Account (from the Chart of Accounts) that is the default income account for this product.
     * This is crucial for automating revenue recognition when the product is sold, impacting the Income Statement [4].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function incomeAccount()
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    /**
     * Get the Account (from the Chart of Accounts) that is the default expense account for this product.
     * This enables automated cost allocation and impacts the Expense section of the Income Statement, aligning with the double-entry principle [4].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    /**
     * Scope a query to only include active products.
     * This is a common query scope to filter out inactive items in various application contexts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to find a product by its SKU within a specific company.
     * SKU should be unique per company to prevent data duplication and maintain accurate inventory records [4].
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sku
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySku($query, $sku, $companyId)
    {
        return $query->where('sku', $sku)->where('company_id', $companyId);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function vendorBillLines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class);
    }
}
