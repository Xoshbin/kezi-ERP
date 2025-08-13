<?php

namespace App\Services\Payments;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterCompanyPaymentService
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly LockDateService $lockDateService
    ) {}

    /**
     * Process inter-company payment and create necessary loan entries
     */
    public function processInterCompanyPayment(Payment $payment, User $user): void
    {
        $interCompanyBills = $this->getInterCompanyVendorBills($payment);
        
        if ($interCompanyBills->isEmpty()) {
            return; // Not an inter-company payment
        }

        DB::transaction(function () use ($payment, $interCompanyBills, $user) {
            foreach ($interCompanyBills as $vendorBill) {
                $this->createInterCompanyLoanEntries($payment, $vendorBill, $user);
            }
        });

        Log::info("Processed inter-company payment {$payment->id} with {$interCompanyBills->count()} inter-company vendor bills");
    }

    /**
     * Get vendor bills that are inter-company (vendor is linked to another company)
     */
    public function getInterCompanyVendorBills(Payment $payment): Collection
    {
        return $payment->vendorBills()
            ->whereHas('vendor', function ($query) {
                $query->whereNotNull('linked_company_id');
            })
            ->with(['vendor.linkedCompany', 'company'])
            ->get();
    }

    /**
     * Create inter-company loan journal entries for a specific vendor bill
     */
    protected function createInterCompanyLoanEntries(Payment $payment, VendorBill $vendorBill, User $user): void
    {
        $payingCompany = $payment->company;
        $beneficiaryCompany = $vendorBill->company;
        $vendorLinkedCompany = $vendorBill->vendor->linkedCompany;

        // Validate that the vendor's linked company is the paying company
        if (!$vendorLinkedCompany || $vendorLinkedCompany->id !== $payingCompany->id) {
            Log::warning("Inter-company payment validation failed: vendor {$vendorBill->vendor->id} not linked to paying company {$payingCompany->id}");
            return;
        }

        // Get the payment amount for this specific vendor bill
        $paymentLink = $payment->paymentDocumentLinks()
            ->where('vendor_bill_id', $vendorBill->id)
            ->first();

        if (!$paymentLink) {
            Log::warning("No payment link found for vendor bill {$vendorBill->id} in payment {$payment->id}");
            return;
        }

        $loanAmount = $paymentLink->amount_applied;

        // Create receivable entry in paying company
        $this->createReceivableEntry($payingCompany, $beneficiaryCompany, $loanAmount, $vendorBill, $user);

        // Create payable entry in beneficiary company
        $this->createPayableEntry($beneficiaryCompany, $payingCompany, $loanAmount, $vendorBill, $user);
    }

    /**
     * Create receivable entry in paying company (they are owed money)
     */
    protected function createReceivableEntry(
        Company $payingCompany,
        Company $beneficiaryCompany,
        Money $loanAmount,
        VendorBill $vendorBill,
        User $user
    ): void {
        // Enforce lock date
        $this->lockDateService->enforce($payingCompany, $vendorBill->bill_date);

        // Find the partner representing the beneficiary company
        $partner = Partner::where('company_id', $payingCompany->id)
            ->where('linked_company_id', $beneficiaryCompany->id)
            ->first();

        if (!$partner) {
            Log::warning("Cannot create inter-company receivable: no partner found for company {$beneficiaryCompany->id} in company {$payingCompany->id}");
            return;
        }

        // Create journal entry lines
        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $partner->receivable_account_id ?? $payingCompany->default_accounts_receivable_id,
                debit_amount: $loanAmount,
                credit_amount: Money::zero($loanAmount->getCurrency()),
                description: "Inter-company loan to {$beneficiaryCompany->name} for vendor bill {$vendorBill->bill_reference}",
                partner_id: $partner->id,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $payingCompany->default_bank_account_id,
                debit_amount: Money::zero($loanAmount->getCurrency()),
                credit_amount: $loanAmount,
                description: "Payment made on behalf of {$beneficiaryCompany->name}",
                partner_id: null,
            ),
        ];

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payingCompany->id,
            journal_id: $payingCompany->default_bank_journal_id,
            entry_date: $vendorBill->bill_date,
            reference: "IC-PAYMENT-{$vendorBill->id}",
            description: "Inter-company payment for {$beneficiaryCompany->name} vendor bill {$vendorBill->bill_reference}",
            lines: $lines,
            created_by_user_id: $user->id,
        );

        $this->createJournalEntryAction->execute($journalEntryDTO);

        Log::info("Created inter-company receivable entry in company {$payingCompany->id} for {$loanAmount->getAmount()} to company {$beneficiaryCompany->id}");
    }

    /**
     * Create payable entry in beneficiary company (they owe money)
     */
    protected function createPayableEntry(
        Company $beneficiaryCompany,
        Company $payingCompany,
        Money $loanAmount,
        VendorBill $vendorBill,
        User $user
    ): void {
        // Enforce lock date
        $this->lockDateService->enforce($beneficiaryCompany, $vendorBill->bill_date);

        // Find the partner representing the paying company
        $partner = Partner::where('company_id', $beneficiaryCompany->id)
            ->where('linked_company_id', $payingCompany->id)
            ->first();

        if (!$partner) {
            Log::warning("Cannot create inter-company payable: no partner found for company {$payingCompany->id} in company {$beneficiaryCompany->id}");
            return;
        }

        // Create journal entry lines
        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $vendorBill->vendor->payable_account_id ?? $beneficiaryCompany->default_accounts_payable_id,
                debit_amount: $loanAmount,
                credit_amount: Money::zero($loanAmount->getCurrency()),
                description: "Vendor bill paid by {$payingCompany->name}",
                partner_id: $vendorBill->vendor_id,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $partner->payable_account_id ?? $beneficiaryCompany->default_accounts_payable_id,
                debit_amount: Money::zero($loanAmount->getCurrency()),
                credit_amount: $loanAmount,
                description: "Inter-company loan from {$payingCompany->name} for vendor bill {$vendorBill->bill_reference}",
                partner_id: $partner->id,
            ),
        ];

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $beneficiaryCompany->id,
            journal_id: $beneficiaryCompany->default_purchase_journal_id,
            entry_date: $vendorBill->bill_date,
            reference: "IC-LOAN-{$vendorBill->id}",
            description: "Inter-company loan from {$payingCompany->name} for vendor bill {$vendorBill->bill_reference}",
            lines: $lines,
            created_by_user_id: $user->id,
        );

        $this->createJournalEntryAction->execute($journalEntryDTO);

        Log::info("Created inter-company payable entry in company {$beneficiaryCompany->id} for {$loanAmount->getAmount()} to company {$payingCompany->id}");
    }

    /**
     * Check if a payment involves inter-company transactions
     */
    public function isInterCompanyPayment(Payment $payment): bool
    {
        return $this->getInterCompanyVendorBills($payment)->isNotEmpty();
    }

    /**
     * Get inter-company loan balance between two companies
     */
    public function getInterCompanyLoanBalance(Company $company1, Company $company2): Money
    {
        // Find partner representing company2 in company1's books
        $partner = Partner::where('company_id', $company1->id)
            ->where('linked_company_id', $company2->id)
            ->first();

        if (!$partner) {
            return Money::zero($company1->currency->code);
        }

        // Calculate balance from receivable account
        // This would require querying journal entry lines - simplified for now
        return Money::zero($company1->currency->code);
    }

    /**
     * Create settlement entry to clear inter-company loans
     */
    public function createSettlementEntry(
        Company $payingCompany,
        Company $receivingCompany,
        Money $settlementAmount,
        User $user,
        string $reference = null
    ): void {
        DB::transaction(function () use ($payingCompany, $receivingCompany, $settlementAmount, $user, $reference) {
            // Create settlement entries in both companies
            $this->createSettlementInPayingCompany($payingCompany, $receivingCompany, $settlementAmount, $user, $reference);
            $this->createSettlementInReceivingCompany($receivingCompany, $payingCompany, $settlementAmount, $user, $reference);
        });
    }

    /**
     * Create settlement entry in paying company
     */
    protected function createSettlementInPayingCompany(
        Company $payingCompany,
        Company $receivingCompany,
        Money $settlementAmount,
        User $user,
        ?string $reference
    ): void {
        // Find partner
        $partner = Partner::where('company_id', $payingCompany->id)
            ->where('linked_company_id', $receivingCompany->id)
            ->first();

        if (!$partner) {
            throw new \InvalidArgumentException("No partner relationship found between companies");
        }

        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $partner->receivable_account_id ?? $payingCompany->default_accounts_receivable_id,
                debit_amount: Money::zero($settlementAmount->getCurrency()),
                credit_amount: $settlementAmount,
                description: "Settlement of inter-company loan from {$receivingCompany->name}",
                partner_id: $partner->id,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $payingCompany->default_bank_account_id,
                debit_amount: $settlementAmount,
                credit_amount: Money::zero($settlementAmount->getCurrency()),
                description: "Inter-company settlement received from {$receivingCompany->name}",
                partner_id: null,
            ),
        ];

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payingCompany->id,
            journal_id: $payingCompany->default_bank_journal_id,
            entry_date: now(),
            reference: $reference ?? "IC-SETTLEMENT-" . now()->format('YmdHis'),
            description: "Inter-company loan settlement from {$receivingCompany->name}",
            lines: $lines,
            created_by_user_id: $user->id,
        );

        $this->createJournalEntryAction->execute($journalEntryDTO);
    }

    /**
     * Create settlement entry in receiving company
     */
    protected function createSettlementInReceivingCompany(
        Company $receivingCompany,
        Company $payingCompany,
        Money $settlementAmount,
        User $user,
        ?string $reference
    ): void {
        // Find partner
        $partner = Partner::where('company_id', $receivingCompany->id)
            ->where('linked_company_id', $payingCompany->id)
            ->first();

        if (!$partner) {
            throw new \InvalidArgumentException("No partner relationship found between companies");
        }

        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $partner->payable_account_id ?? $receivingCompany->default_accounts_payable_id,
                debit_amount: $settlementAmount,
                credit_amount: Money::zero($settlementAmount->getCurrency()),
                description: "Settlement of inter-company loan to {$payingCompany->name}",
                partner_id: $partner->id,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $receivingCompany->default_bank_account_id,
                debit_amount: Money::zero($settlementAmount->getCurrency()),
                credit_amount: $settlementAmount,
                description: "Inter-company settlement paid to {$payingCompany->name}",
                partner_id: null,
            ),
        ];

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $receivingCompany->id,
            journal_id: $receivingCompany->default_bank_journal_id,
            entry_date: now(),
            reference: $reference ?? "IC-SETTLEMENT-" . now()->format('YmdHis'),
            description: "Inter-company loan settlement to {$payingCompany->name}",
            lines: $lines,
            created_by_user_id: $user->id,
        );

        $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
