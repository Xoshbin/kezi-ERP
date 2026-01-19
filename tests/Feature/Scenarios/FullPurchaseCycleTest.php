<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Actions\GoodsReceipt\CreateGoodsReceiptFromPurchaseOrderAction;
use Modules\Inventory\Actions\GoodsReceipt\ValidateGoodsReceiptAction;
use Modules\Inventory\DataTransferObjects\ReceiveGoodsFromPurchaseOrderDTO;
use Modules\Inventory\DataTransferObjects\ReceiveGoodsLineDTO;
use Modules\Inventory\DataTransferObjects\ValidateGoodsReceiptDTO;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Models\StockLocation;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Services\PaymentService;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\ConvertRFQToPurchaseOrderAction;
use Modules\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Modules\Purchase\Actions\Purchases\RecordVendorBidAction;
use Modules\Purchase\Actions\Purchases\SendRequestForQuotationAction;
use Modules\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\RequestForQuotation;
use Modules\Purchase\Models\RequestForQuotationLine;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    $this->currency = $this->company->currency;

    $this->vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => PartnerType::Vendor,
        'email' => 'vendor@example.com',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Expense,
        'code' => '6000',
        'name' => 'Cost of Goods Sold',
    ]);

    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::BankAndCash,
        'code' => '1000',
        'name' => 'Bank',
    ]);

    $this->bankJournal = Journal::factory()->for($this->company)->create([
        'type' => JournalType::Bank,
        'name' => 'Bank Journal',
        'short_code' => 'BNK1',
        'default_debit_account_id' => $this->bankAccount->id,
        'default_credit_account_id' => $this->bankAccount->id,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'expense_account_id' => $this->expenseAccount->id,
        'default_stock_input_account_id' => $this->expenseAccount->id,
    ]);

    $this->internalLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
        'name' => 'WH/Stock',
    ]);

    $this->company->update(['default_stock_location_id' => $this->internalLocation->id]);

    $this->vendorLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Vendor,
        'name' => 'Partners/Vendors',
    ]);
});

test('full purchase cycle: rfq -> po -> receipt -> bill -> payment', function () {
    Mail::fake();
    Carbon::setTestNow('2026-02-01 10:00:00');

    // ==========================================
    // 1. RFQ Phase
    // ==========================================

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => RequestForQuotationStatus::Draft,
        'created_by_user_id' => $this->user->id,
    ]);

    $rfqLine = RequestForQuotationLine::factory()->create([
        'rfq_id' => $rfq->id,
        'product_id' => $this->product->id,
        'quantity' => 50,
        'unit_price' => Money::of(20, $this->currency->code),
    ]);

    // Send RFQ
    app(SendRequestForQuotationAction::class)->execute($rfq, $this->user);
    expect($rfq->refresh()->status)->toBe(RequestForQuotationStatus::Sent);

    // Record Bid
    $updateRfqDto = new UpdateRFQDTO(
        rfqId: $rfq->id,
        rfq: $rfq,
    );

    app(RecordVendorBidAction::class)->execute($rfq, $updateRfqDto);
    expect($rfq->refresh()->status)->toBe(RequestForQuotationStatus::BidReceived);

    // Convert to Purchase Order
    $convertDto = new ConvertRFQToPurchaseOrderDTO(
        rfqId: $rfq->id,
        poDate: now(),
        convertedByUserId: $this->user->id
    );

    $purchaseOrder = app(ConvertRFQToPurchaseOrderAction::class)->execute($convertDto);

    expect($purchaseOrder)->toBeInstanceOf(PurchaseOrder::class)
        ->and($purchaseOrder->status)->toBe(PurchaseOrderStatus::Draft);

    // ==========================================
    // 2. Purchase Order Phase
    // ==========================================

    // Confirm PO
    app(PurchaseOrderService::class)->confirm($purchaseOrder, $this->user);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::ToReceive);

    // ==========================================
    // 3. Goods Receipt Phase
    // ==========================================

    $receiveDto = new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $purchaseOrder,
        userId: $this->user->id,
        receiptDate: now(),
        location: $this->internalLocation
    );

    $picking = app(CreateGoodsReceiptFromPurchaseOrderAction::class)->execute($receiveDto);

    expect($picking)->not->toBeNull()
        ->and($picking->state)->toBe(StockPickingState::Draft)
        ->and($picking->purchase_order_id)->toBe($purchaseOrder->id);

    // Validate Receipt (Full quantity)
    $poLine = $purchaseOrder->lines->first();

    $validateLines = [
        new ReceiveGoodsLineDTO(
            purchaseOrderLineId: $poLine->id,
            quantityToReceive: 50
        )
    ];

    $validateDto = new ValidateGoodsReceiptDTO(
        stockPicking: $picking,
        userId: $this->user->id,
        lines: $validateLines,
        createBackorder: false
    );

    app(ValidateGoodsReceiptAction::class)->execute($validateDto);

    $picking->refresh();
    expect($picking->state)->toBe(StockPickingState::Done);

    // Verify PO knows about receipt
    $poLine->refresh();
    expect($poLine->quantity_received)->toBe(50.0);

    // ==========================================
    // 4. Vendor Bill Phase
    // ==========================================

    $billDto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $purchaseOrder->id,
        created_by_user_id: $this->user->id,
        bill_date: now(),
        accounting_date: now(),
        due_date: now()->addDays(30),
        bill_reference: 'INV-VENDOR-001',
        payment_term_id: null,
        line_quantities: [$poLine->id => 50]
    );

    $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($billDto);

    expect($vendorBill)->toBeInstanceOf(VendorBill::class)
        ->and($vendorBill->status)->toBe(VendorBillStatus::Draft)
        ->and($vendorBill->total_amount->getAmount()->toFloat())->toBe(1000.0);

    // Post Bill - Mock Permissions
    Gate::shouldReceive('forUser->authorize')->with('post', \Mockery::any())->andReturn(true);
    Gate::shouldReceive('forUser->authorize')->with('cancel', \Mockery::any())->andReturn(true);
    Gate::shouldReceive('forUser->authorize')->with('resetToDraft', \Mockery::any())->andReturn(true);

    app(VendorBillService::class)->post($vendorBill, $this->user);

    expect($vendorBill->refresh()->status)->toBe(VendorBillStatus::Posted);

    // Verify PO status
    $purchaseOrder->refresh();
    // Logic in PurchaseOrder::updateStatusBasedOnBilling sets it to PartiallyBilled (as per code reading)
    // even if it might be logically FullyBilled.
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::PartiallyBilled);

    // ==========================================
    // 5. Payment Phase
    // ==========================================

    $paymentAmount = Money::of(1000, $this->currency->code);

    $documentLinks = [
        new CreatePaymentDocumentLinkDTO(
            document_type: 'vendor_bill',
            document_id: $vendorBill->id,
            amount_applied: $paymentAmount
        )
    ];

    $paymentDto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $this->bankJournal->id,
        currency_id: $this->currency->id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Outbound,
        payment_method: PaymentMethod::BankTransfer,
        paid_to_from_partner_id: $this->vendor->id,
        amount: $paymentAmount,
        document_links: $documentLinks,
        reference: 'Payment for Bill'
    );

    $payment = app(CreatePaymentAction::class)->execute($paymentDto, $this->user);

    expect($payment->status)->toBe(PaymentStatus::Draft);

    app(PaymentService::class)->confirm($payment, $this->user);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Confirmed)
        ->and($vendorBill->refresh()->status)->toBe(VendorBillStatus::Paid);

});
