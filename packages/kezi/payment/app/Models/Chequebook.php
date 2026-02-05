<?php

namespace Kezi\Payment\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Observers\AuditLogObserver;

#[ObservedBy([AuditLogObserver::class])]
/**
 * @property int $id
 * @property int $company_id
 * @property int $journal_id
 * @property string $name
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $prefix
 * @property int $digits
 * @property int $start_number
 * @property int $end_number
 * @property int $next_number
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Payment\Models\Cheque> $cheques
 * @property-read int|null $cheques_count
 * @property-read Company $company
 * @property-read Journal $journal
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereDigits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereEndNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereNextNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereStartNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chequebook whereUpdatedAt($value)
 * @method static \Kezi\Payment\Database\Factories\ChequebookFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class Chequebook extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'journal_id',
        'name',
        'bank_name',
        'bank_account_number',
        'prefix',
        'digits',
        'start_number',
        'end_number',
        'next_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'digits' => 'integer',
        'start_number' => 'integer',
        'end_number' => 'integer',
        'next_number' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    protected static function newFactory()
    {
        return \Kezi\Payment\Database\Factories\ChequebookFactory::new();
    }
}
