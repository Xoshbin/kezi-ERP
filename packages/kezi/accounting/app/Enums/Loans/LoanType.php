<?php

namespace Kezi\Accounting\Enums\Loans;

enum LoanType: string
{
    case Payable = 'payable';
    case Receivable = 'receivable';
}
