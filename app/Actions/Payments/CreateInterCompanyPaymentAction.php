<?php

namespace App\Actions\Payments;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreateInterCompanyPaymentDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\Payments\InterCompanyPaymentService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateInterCompanyPaymentAction
{
    public function __construct(
        private readonly CreatePaymentAction $createPaymentAction,
        private readonly PaymentService $paymentService,
        private readonly InterCompanyPaymentService $interCompanyPaymentService
    ) {}

    /**
     * Execute inter-company payment where one company pays vendor bills on behalf of another
     */
    public function execute(CreateInterCompanyPaymentDTO $dto, User $user): Payment
    {
        return DB::transaction(function () use ($dto, $user) {
            // Validate the inter-company relationship
            $this->validateInterCompanyRelationship($dto);

            // Validate vendor bills belong to the beneficiary company
            $this->validateVendorBills($dto);

            // Create the payment in the paying company
            $payment = $this->createPayment($dto, $user);

            // Confirm the payment to trigger inter-company processing
            $confirmedPayment = $this->paymentService->confirm($payment, $user);

            Log::info("Created inter-company payment {$confirmedPayment->id} from company {$dto->paying_company_id} for company {$dto->beneficiary_company_id}");

            return $confirmedPayment;
        });
    }

    /**
     * Validate that the companies have a proper inter-company relationship
     */
    protected function validateInterCompanyRelationship(CreateInterCompanyPaymentDTO $dto): void
    {
        $payingCompany = Company::findOrFail($dto->paying_company_id);
        $beneficiaryCompany = Company::findOrFail($dto->beneficiary_company_id);

        // Check if companies are different
        if ($payingCompany->id === $beneficiaryCompany->id) {
            throw new \InvalidArgumentException('Paying company and beneficiary company must be different');
        }

        // Check if there's a partner relationship
        $partnerExists = Partner::where('company_id', $payingCompany->id)
            ->where('linked_company_id', $beneficiaryCompany->id)
            ->exists();

        if (!$partnerExists) {
            throw new \InvalidArgumentException("No partner relationship exists between {$payingCompany->name} and {$beneficiaryCompany->name}");
        }
    }

    /**
     * Validate that all vendor bills belong to the beneficiary company
     */
    protected function validateVendorBills(CreateInterCompanyPaymentDTO $dto): void
    {
        $vendorBillIds = collect($dto->vendor_bill_payments)->pluck('vendor_bill_id');
        
        $invalidBills = VendorBill::whereIn('id', $vendorBillIds)
            ->where('company_id', '!=', $dto->beneficiary_company_id)
            ->count();

        if ($invalidBills > 0) {
            throw new \InvalidArgumentException('All vendor bills must belong to the beneficiary company');
        }

        // Validate that vendor bills have vendors linked to the paying company
        $invalidVendorBills = VendorBill::whereIn('id', $vendorBillIds)
            ->whereHas('vendor', function ($query) use ($dto) {
                $query->where('linked_company_id', '!=', $dto->paying_company_id)
                      ->orWhereNull('linked_company_id');
            })
            ->count();

        if ($invalidVendorBills > 0) {
            throw new \InvalidArgumentException('All vendor bills must have vendors linked to the paying company');
        }
    }

    /**
     * Create the payment in the paying company
     */
    protected function createPayment(CreateInterCompanyPaymentDTO $dto, User $user): Payment
    {
        // Convert vendor bill payments to payment document links
        $documentLinks = collect($dto->vendor_bill_payments)->map(function ($vendorBillPayment) {
            return new CreatePaymentDocumentLinkDTO(
                document_type: 'vendor_bill',
                document_id: $vendorBillPayment->vendor_bill_id,
                amount_applied: $vendorBillPayment->amount_applied,
            );
        })->toArray();

        // Create the payment DTO
        $paymentDTO = new CreatePaymentDTO(
            company_id: $dto->paying_company_id,
            journal_id: $dto->journal_id,
            currency_id: $dto->currency_id,
            payment_date: $dto->payment_date,
            document_links: $documentLinks,
            reference: $dto->reference,
        );

        return $this->createPaymentAction->execute($paymentDTO, $user);
    }

    /**
     * Create settlement payment to clear inter-company loans
     */
    public function createSettlementPayment(
        Company $payingCompany,
        Company $receivingCompany,
        \Brick\Money\Money $settlementAmount,
        User $user,
        ?string $reference = null
    ): void {
        $this->interCompanyPaymentService->createSettlementEntry(
            $payingCompany,
            $receivingCompany,
            $settlementAmount,
            $user,
            $reference
        );

        Log::info("Created inter-company settlement from {$payingCompany->name} to {$receivingCompany->name} for {$settlementAmount->getAmount()}");
    }

    /**
     * Get inter-company loan balance between two companies
     */
    public function getInterCompanyBalance(Company $company1, Company $company2): \Brick\Money\Money
    {
        return $this->interCompanyPaymentService->getInterCompanyLoanBalance($company1, $company2);
    }

    /**
     * Validate that a payment can be processed as inter-company
     */
    public function canProcessAsInterCompany(Payment $payment): bool
    {
        return $this->interCompanyPaymentService->isInterCompanyPayment($payment);
    }
}
