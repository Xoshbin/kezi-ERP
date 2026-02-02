<?php

namespace Kezi\Accounting\DataTransferObjects\Accounting;

use App\Models\User;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\BankStatementLine;

readonly class CreateJournalEntryForStatementLineDTO
{
    public function __construct(
        public BankStatementLine $bankStatementLine,
        public Account $writeOffAccount,
        public User $user,
        public string $description,
    ) {}
}
