<?php

namespace Modules\Accounting\Enums\Loans;

enum LoanStatus: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Closed = 'closed';
}
