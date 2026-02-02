<?php

namespace Kezi\Sales\Tests\Feature\Actions\Invoice;

use Brick\Money\Money;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Sales\Actions\Invoice\CreateCreditNoteAction;
use Kezi\Sales\DataTransferObjects\Invoice\CreateCreditNoteDTO;
use Kezi\Sales\DataTransferObjects\Invoice\CreateCreditNoteLineDTO;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

/**
 * @var \Tests\TestCase $this
 *
 * @property \App\Models\Company $company
 * @property \App\Models\User $user
 * @property \Kezi\Sales\Models\Invoice $invoice
 * @property \Kezi\Product\Models\Product $product
 * @property \Kezi\Accounting\Models\Account $salesDiscountAccount
 * @property \Kezi\Accounting\Models\Account $taxAccount
 * @property \Kezi\Accounting\Models\Account $arAccount
 * @property \Kezi\Accounting\Models\Journal $salesJournal
 */
uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Setup Validation Accounts for Credit Note Journal Entry
    $this->salesDiscountAccount = Account::factory()->for($this->company)->create(['name' => 'Sales Discount']);
    $this->taxAccount = Account::factory()->for($this->company)->create(['name' => 'Tax Payable']);
    $this->arAccount = Account::factory()->for($this->company)->create(['name' => 'Accounts Receivable']);
    $this->salesJournal = \Kezi\Accounting\Models\Journal::factory()->for($this->company)->create(['name' => 'Sales Journal', 'type' => 'sale']);

    $this->company->update([
        'default_sales_discount_account_id' => $this->salesDiscountAccount->id,
        'default_tax_account_id' => $this->taxAccount->id,
        'default_accounts_receivable_id' => $this->arAccount->id,
        'default_sales_journal_id' => $this->salesJournal->id,
    ]);

    // Setup Product and Invoice
    // Ensure Currency is USD standard
    $this->company->currency->update(['code' => 'USD', 'symbol' => '$', 'precision' => 2]);

    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'unit_price' => Money::of(100, 'USD'),
    ]);

    $this->invoice = Invoice::factory()->for($this->company)->create([
        'status' => InvoiceStatus::Posted,
        'currency_id' => $this->company->currency_id,
        'invoice_date' => now()->toDateString(),
    ]);
});

it('can create a credit note for an invoice', function () {
    $date = now()->toDateString();

    $lineDto = new CreateCreditNoteLineDTO(
        description: 'Return of Goods',
        quantity: 1,
        unit_price: Money::of(100, 'USD'),
        account_id: $this->salesDiscountAccount->id,
        product_id: $this->product->id,
        tax_id: null,
    );

    $dto = new CreateCreditNoteDTO(
        company_id: $this->company->id,
        invoice_id: $this->invoice->id,
        date: $date,
        reason: 'Defective Goods',
        lines: [$lineDto],
    );

    $action = app(CreateCreditNoteAction::class);
    $creditNote = $action->execute($dto);

    // Assert Database
    $this->assertDatabaseHas('adjustment_documents', [
        'id' => $creditNote->id,
        'type' => AdjustmentDocumentType::CreditNote->value,
        'company_id' => $this->company->id,
        'original_invoice_id' => $this->invoice->id,
    ]);

    expect($creditNote->status->value)->toBe(AdjustmentDocumentStatus::Draft->value);

    expect($creditNote->lines)->toHaveCount(1)
        ->and($creditNote->subtotal->getAmount()->toInt())->toBe(100)
        ->and($creditNote->total_amount->getAmount()->toInt())->toBe(100);
});

it('creates stock moves when credit note is posted', function () {
    $date = now()->toDateString();

    $lineDto = new CreateCreditNoteLineDTO(
        description: 'Return of Goods',
        quantity: 5,
        unit_price: Money::of(100, 'USD'),
        account_id: $this->salesDiscountAccount->id,
        product_id: $this->product->id,
        tax_id: null,
    );

    $dto = new CreateCreditNoteDTO(
        company_id: $this->company->id,
        invoice_id: $this->invoice->id,
        date: $date,
        reason: 'Defective Goods',
        lines: [$lineDto],
    );

    $action = app(CreateCreditNoteAction::class);
    $creditNote = $action->execute($dto);

    $creditNote->update(['status' => AdjustmentDocumentStatus::Posted, 'posted_at' => now()]);

    \Kezi\Inventory\Events\AdjustmentDocumentPosted::dispatch($creditNote);

    // Verify Stock Moves
    $customerLoc = StockLocation::where('company_id', $this->company->id)
        ->where('type', StockLocationType::Customer)
        ->first();
    $internalLoc = StockLocation::where('company_id', $this->company->id)
        ->where('type', StockLocationType::Internal)
        ->first();

    // Type hinting for PHPStan
    assert($customerLoc instanceof StockLocation);
    assert($internalLoc instanceof StockLocation);

    $stockMove = \Kezi\Inventory\Models\StockMove::where('source_id', $creditNote->id)
        ->where('source_type', AdjustmentDocument::class)
        ->first();

    // Type hinting for PHPStan
    assert($stockMove instanceof \Kezi\Inventory\Models\StockMove);

    expect($stockMove)->not->toBeNull()
        ->and($stockMove->productLines)->toHaveCount(1);

    $line = $stockMove->productLines->first();
    assert($line instanceof \Kezi\Inventory\Models\StockMoveProductLine);

    expect($line->product_id)->toBe($this->product->id)
        ->and((float) $line->quantity)->toBe(5.0)
        ->and($line->from_location_id)->toBe($customerLoc->id)
        ->and($line->to_location_id)->toBe($internalLoc->id);
});

it('creates journal entry when credit note is posted', function () {
    // Use 0.10 for 10% rate as per Model expectation
    $tax = \Kezi\Accounting\Models\Tax::factory()->for($this->company)->create(['rate' => 0.10, 'tax_account_id' => $this->taxAccount->id]);

    $date = now()->toDateString();

    $lineDto = new CreateCreditNoteLineDTO(
        description: 'Return of Goods',
        quantity: 1,
        unit_price: Money::of(100, 'USD'),
        account_id: $this->salesDiscountAccount->id,
        product_id: $this->product->id,
        tax_id: $tax->id,
    );

    $dto = new CreateCreditNoteDTO(
        company_id: $this->company->id,
        invoice_id: $this->invoice->id,
        date: $date,
        reason: 'Defective Goods',
        lines: [$lineDto],
    );

    $action = app(CreateCreditNoteAction::class);
    $creditNote = $action->execute($dto);

    // Refresh to ensure we get fresh state from DB (triggering casts)
    $creditNote->refresh();

    $creditNote->update(['status' => AdjustmentDocumentStatus::Posted, 'posted_at' => now()]);

    $jeAction = app(\Kezi\Accounting\Actions\Accounting\CreateJournalEntryForAdjustmentAction::class);
    $journalEntry = $jeAction->execute($creditNote, $this->user);

    expect($journalEntry)->toBeInstanceOf(JournalEntry::class)
        ->and($journalEntry->lines)->toHaveCount(3);

    // 1. Debit Sales Discount (100)
    $discountLine = $journalEntry->lines->where('account_id', $this->salesDiscountAccount->id)->first();
    assert($discountLine instanceof \Kezi\Accounting\Models\JournalEntryLine);

    expect($discountLine)->not->toBeNull()
        ->and($discountLine->debit->getAmount()->toInt())->toBe(100);

    // 2. Debit Tax (10)
    $taxLine = $journalEntry->lines->where('account_id', $this->taxAccount->id)->first();
    assert($taxLine instanceof \Kezi\Accounting\Models\JournalEntryLine);

    expect($taxLine)->not->toBeNull()
        ->and($taxLine->debit->getAmount()->toInt())->toBe(10);

    // 3. Credit AR (110)
    $arLine = $journalEntry->lines->where('account_id', $this->arAccount->id)->first();
    assert($arLine instanceof \Kezi\Accounting\Models\JournalEntryLine);

    expect($arLine)->not->toBeNull()
        ->and($arLine->credit->getAmount()->toInt())->toBe(110);
});

it('validates credit note creation', function () {
    $otherCompany = \App\Models\Company::factory()->create();
    $dto = new CreateCreditNoteDTO(
        company_id: $otherCompany->id,
        invoice_id: $this->invoice->id,
        date: now()->toDateString(),
        reason: 'Test',
        lines: []
    );

    expect(fn () => app(CreateCreditNoteAction::class)->execute($dto))
        ->toThrow(\Exception::class, 'Invoice does not belong to the requested company.');

    $draftInvoice = Invoice::factory()->for($this->company)->create(['status' => InvoiceStatus::Draft]);
    $dto2 = new CreateCreditNoteDTO(
        company_id: $this->company->id,
        invoice_id: $draftInvoice->id,
        date: now()->toDateString(),
        reason: 'Test',
        lines: []
    );

    expect(fn () => app(CreateCreditNoteAction::class)->execute($dto2))
        ->toThrow(\Exception::class, 'Credit notes can only be created for confirmed/posted invoices.');
});
