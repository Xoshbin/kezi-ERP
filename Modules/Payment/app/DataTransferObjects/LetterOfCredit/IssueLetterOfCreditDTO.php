<?php

namespace Modules\Payment\DataTransferObjects\LetterOfCredit;

readonly class IssueLetterOfCreditDTO
{
    public function __construct(
        public string $bank_reference,
        public \Illuminate\Support\Carbon $issue_date,
    ) {}
}
