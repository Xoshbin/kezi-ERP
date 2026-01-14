<?php

namespace Modules\Inventory\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Purchase\Models\VendorBill;

/**
 * @property int $id
 * @property int $company_id
 * @property LandedCostStatus $status
 * @property \Illuminate\Support\Carbon $date
 * @property \Brick\Money\Money $amount_total
 * @property string|null $description
 * @property int|null $vendor_bill_id
 * @property int|null $journal_entry_id
 * @property LandedCostAllocationMethod $allocation_method
 * @property int|null $created_by_user_id
 * @property-read Company $company
 * @property-read VendorBill|null $vendorBill
 * @property-read JournalEntry|null $journalEntry
 * @property-read User|null $createdByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LandedCostLine> $lines
 */
#[ObservedBy([AuditLogObserver::class])]
class LandedCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'status',
        'date',
        'amount_total',
        'description',
        'vendor_bill_id',
        'journal_entry_id',
        'allocation_method',
        'created_by_user_id',
    ];

    protected $casts = [
        'status' => LandedCostStatus::class,
        'date' => 'date',
        'amount_total' => BaseCurrencyMoneyCast::class,
        'allocation_method' => LandedCostAllocationMethod::class,
    ];

    protected $with = ['company.currency'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<LandedCostLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LandedCostLine::class);
    }

    /**
     * Get the stock pickings that this landed cost applies to.
     */
    public function stockPickings(): BelongsToMany
    {
        return $this->belongsToMany(
            StockPicking::class,
            'landed_cost_stock_picking',
            'landed_cost_id',
            'stock_picking_id'
        )->withTimestamps();
    }
}
