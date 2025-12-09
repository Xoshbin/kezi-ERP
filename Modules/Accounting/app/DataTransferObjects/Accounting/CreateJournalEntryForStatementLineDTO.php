<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

use App\Models\User;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\BankStatementLine;

readonly class CreateJournalEntryForStatementLineDTO
{
    public function __construct(
        public BankStatementLine $bankStatementLine,
        public Account $writeOffAccount,
        public User $user,
        public string $description,
    ) {}
}
