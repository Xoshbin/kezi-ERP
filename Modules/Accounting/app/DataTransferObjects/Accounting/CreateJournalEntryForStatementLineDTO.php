<?php

namespace App\DataTransferObjects\Accounting;

use App\Models\Account;
use App\Models\BankStatementLine;
use App\Models\User;

readonly class CreateJournalEntryForStatementLineDTO
{
    public function __construct(
        public BankStatementLine $bankStatementLine,
        public Account $writeOffAccount,
        public User $user,
        public string $description,
    ) {}
}
