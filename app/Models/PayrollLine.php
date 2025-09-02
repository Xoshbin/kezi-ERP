<?php

namespace App\Models;

use App\Casts\PayrollCurrencyMoneyCast;
use App\Casts\BaseCurrencyMoneyCast;
use Illuminate\Database\Eloquent\Model;

class PayrollLine extends Model
{
    protected $fillable = [
        'company_id',
        'payroll_id',
        'account_id',
        'line_type',
        'code',
        'description',
        'quantity',
        'unit',
        'rate',
        'amount',
        'amount_company_currency',
        'tax_rate',
        'is_taxable',
        'is_statutory',
        'debit_credit',
        'analytic_account_id',
        'notes',
        'reference',
    ];

    protected $casts = [
        'description' => 'array',
        'quantity' => 'decimal:4',
        'rate' => PayrollCurrencyMoneyCast::class,
        'amount' => PayrollCurrencyMoneyCast::class,
        'amount_company_currency' => BaseCurrencyMoneyCast::class,
        'tax_rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function analyticAccount()
    {
        return $this->belongsTo(AnalyticAccount::class);
    }
}
