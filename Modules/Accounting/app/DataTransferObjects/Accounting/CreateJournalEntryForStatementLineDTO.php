<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

use App\Models\User;

readonly class CreateJournalEntryForStatementLineDTO
{
    public function __construct(
        public \Modules\Accounting\Models\BankStatementLine $bankStatementLine,
        public \Modules\Accounting\Models\Account $writeOffAccount,
        public User $user,
        public string $description,
    ) {}
}
