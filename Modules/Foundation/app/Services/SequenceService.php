<?php

namespace Modules\Foundation\Services;

use App\Enums\Settings\NumberingType;
use App\Models\Company;
use App\Models\Sequence;
use Carbon\Carbon;

/**
 * SequenceService
 *
 * Provides atomic sequential number generation for different document types.
 * This service ensures race-condition-free number assignment following
 * accounting best practices and immutability principles.
 */
class SequenceService
{
    /**
     * Generate the next invoice number for a company.
     *
     * @param  Carbon|null  $date  The invoice date for date-based numbering
     */
    public function getNextInvoiceNumber(Company $company, ?Carbon $date = null): string
    {
        $config = $company->getInvoiceNumberingConfig();
        $numberingType = NumberingType::from($config['type']);

        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'invoice',
            prefix: $config['prefix'],
            padding: $config['padding']
        );

        $nextNumber = $sequence->getNextNumber();

        // For simple format, return as-is (already formatted by Sequence model)
        if ($numberingType === NumberingType::SIMPLE) {
            return $nextNumber;
        }

        // For other formats, extract the number and reformat
        $parts = explode('-', $nextNumber);
        $sequentialNumber = (int) end($parts);

        return $numberingType->formatNumber(
            $config['prefix'],
            $sequentialNumber,
            $config['padding'],
            $date ?? now()
        );
    }

    /**
     * Generate the next vendor bill number for a company.
     *
     * @param  Carbon|null  $date  The bill date for date-based numbering
     */
    public function getNextVendorBillNumber(Company $company, ?Carbon $date = null): string
    {
        $config = $company->getVendorBillNumberingConfig();
        $numberingType = NumberingType::from($config['type']);

        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'vendor_bill',
            prefix: $config['prefix'],
            padding: $config['padding']
        );

        $nextNumber = $sequence->getNextNumber();

        // For simple format, return as-is (already formatted by Sequence model)
        if ($numberingType === NumberingType::SIMPLE) {
            return $nextNumber;
        }

        // For other formats, extract the number and reformat
        $parts = explode('-', $nextNumber);
        $sequentialNumber = (int) end($parts);

        return $numberingType->formatNumber(
            $config['prefix'],
            $sequentialNumber,
            $config['padding'],
            $date ?? now()
        );
    }

    /**
     * Generate the next payment number for a company.
     */
    public function getNextPaymentNumber(Company $company): string
    {
        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'payment',
            prefix: 'PAY',
            padding: 5
        );

        return $sequence->getNextNumber();
    }

    /**
     * Generate the next credit note number for a company.
     */
    public function getNextCreditNoteNumber(Company $company): string
    {
        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'credit_note',
            prefix: 'CN',
            padding: 5
        );

        return $sequence->getNextNumber();
    }

    /**
     * Generate the next journal entry number for a company.
     */
    public function getNextJournalEntryNumber(Company $company): string
    {
        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'journal_entry',
            prefix: 'JE',
            padding: 5
        );

        return $sequence->getNextNumber();
    }

    /**
     * Generate the next number for any document type.
     */
    public function getNextNumber(
        Company $company,
        string $documentType,
        string $prefix,
        int $padding = 5
    ): string {
        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: $documentType,
            prefix: $prefix,
            padding: $padding
        );

        return $sequence->getNextNumber();
    }

    /**
     * Get the current number for a document type without incrementing.
     */
    public function getCurrentNumber(Company $company, string $documentType): int
    {
        $sequence = Sequence::where('company_id', $company->id)
            ->where('document_type', $documentType)
            ->first();

        return $sequence ? $sequence->current_number : 0;
    }

    /**
     * Reset a sequence to a specific number (use with caution).
     * This should only be used during data migration or setup.
     */
    public function resetSequence(Company $company, string $documentType, int $number): void
    {
        $sequence = Sequence::where('company_id', $company->id)
            ->where('document_type', $documentType)
            ->first();

        if ($sequence) {
            $sequence->update(['current_number' => $number]);
        }
    }
}
