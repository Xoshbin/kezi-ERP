<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Sequence;

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
     * @param Company $company
     * @return string
     */
    public function getNextInvoiceNumber(Company $company): string
    {
        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'invoice',
            prefix: 'INV',
            padding: 5
        );

        return $sequence->getNextNumber();
    }

    /**
     * Generate the next vendor bill number for a company.
     * 
     * @param Company $company
     * @return string
     */
    public function getNextVendorBillNumber(Company $company): string
    {
        $sequence = Sequence::getOrCreateSequence(
            companyId: $company->id,
            documentType: 'vendor_bill',
            prefix: 'BILL',
            padding: 5
        );

        return $sequence->getNextNumber();
    }

    /**
     * Generate the next payment number for a company.
     * 
     * @param Company $company
     * @return string
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
     * 
     * @param Company $company
     * @return string
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
     * 
     * @param Company $company
     * @return string
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
     * 
     * @param Company $company
     * @param string $documentType
     * @param string $prefix
     * @param int $padding
     * @return string
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
     * 
     * @param Company $company
     * @param string $documentType
     * @return int
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
     * 
     * @param Company $company
     * @param string $documentType
     * @param int $number
     * @return void
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
