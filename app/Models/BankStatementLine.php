<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $bank_statement_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $description
 * @property string|null $partner_name
 * @property float $amount
 * @property bool $is_reconciled
 * @property int|null $payment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankStatement $bankStatement
 * @property-read \App\Models\Payment|null $payment
 * @method static \Database\Factories\BankStatementLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereBankStatementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereIsReconciled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine wherePartnerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankStatementLine extends Model
{
    /** @use HasFactory<\Database\Factories\BankStatementLineFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'date',
        'description',
        'partner_name',
        'amount',
        'is_reconciled',
        'payment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => MoneyCast::class,
        'is_reconciled' => 'boolean'
    ];
    public function bankStatement()
    {
        return $this->belongsTo(BankStatement::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
