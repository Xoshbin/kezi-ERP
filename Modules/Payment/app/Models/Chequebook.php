<?php

namespace Modules\Payment\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Observers\AuditLogObserver;

#[ObservedBy([AuditLogObserver::class])]
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
        return \Modules\Payment\Database\Factories\ChequebookFactory::new();
    }
}
