<?php

namespace Modules\Accounting\DataTransferObjects;

readonly class DunningLevelDTO
{
    public function __construct(
        public int $company_id,
        public string $name,
        public int $days_overdue,
        public ?string $email_subject = null,
        public ?string $email_body = null,
        public bool $print_letter = false,
        public bool $send_email = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            company_id: $data['company_id'],
            name: $data['name'],
            days_overdue: (int) $data['days_overdue'],
            email_subject: $data['email_subject'] ?? null,
            email_body: $data['email_body'] ?? null,
            print_letter: (bool) ($data['print_letter'] ?? false),
            send_email: (bool) ($data['send_email'] ?? true),
        );
    }
}
