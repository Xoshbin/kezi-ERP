<?php

namespace Jmeryar\Accounting\Exceptions\Reconciliation;

/**
 * Exception thrown when attempting to reconcile journal lines with different partners.
 *
 * This exception is thrown when journal entry lines for A/R or A/P reconciliation
 * belong to different partners, which violates the business rule that reconciliation
 * should only occur within the same partner's transactions.
 */
class PartnerMismatchException extends ReconciliationException
{
    /**
     * @param  array<int, string>  $partnerNames
     */
    public function __construct(array $partnerNames = [], ?string $message = null)
    {
        $message = $message ?? sprintf(
            'All journal entry lines must belong to the same partner for A/R and A/P reconciliation. Found partners: %s',
            implode(', ', $partnerNames)
        );

        parent::__construct($message);
    }
}
