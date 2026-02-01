<?php

namespace Jmeryar\Accounting\DataTransferObjects\Accounting;

use App\Models\User;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\BankStatementLine;

readonly class CreateJournalEntryForStatementLineDTO
{
    public function __construct(
        public BankStatementLine $bankStatementLine,
        public Account $writeOffAccount,
        public User $user,
        public string $description,
    ) {}
}
