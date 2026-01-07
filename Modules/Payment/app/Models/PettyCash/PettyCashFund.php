<?php

namespace Modules\Payment\Models\PettyCash;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Payment\Enums\PettyCash\PettyCashFundStatus;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property int $custodian_id
 * @property int $account_id
 * @property int $bank_account_id
 * @property int $currency_id
 * @property \Brick\Money\Money $imprest_amount
 * @property \Brick\Money\Money $current_balance
 * @property PettyCashFundStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read User $custodian
 * @property-read Account $account
 * @property-read Account $bankAccount
 * @property-read Currency $currency
 */
#[ObservedBy([AuditLogObserver::class])]
class PettyCashFund extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Modules\Payment\Database\Factories\PettyCash\PettyCashFundFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'custodian_id',
        'account_id',
        'bank_account_id',
        'currency_id',
        'imprest_amount',
        'current_balance',
        'status',
    ];

    protected $casts = [
        'imprest_amount' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'current_balance' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'status' => PettyCashFundStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(PettyCashVoucher::class, 'fund_id');
    }

    public function replenishments(): HasMany
    {
        return $this->hasMany(PettyCashReplenishment::class, 'fund_id');
    }
}
