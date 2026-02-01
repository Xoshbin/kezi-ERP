<?php

namespace Kezi\Manufacturing\Tests\Feature\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Manufacturing\Actions\Accounting\CreateJournalEntryForManufacturingAction;
use Kezi\Manufacturing\Models\ManufacturingOrder;
use Kezi\Manufacturing\Models\ManufacturingOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class, RefreshDatabase::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    // Setup Accounts
    $this->rmAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1010',
        'name' => 'Raw Materials',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $this->fgAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1020',
        'name' => 'Finished Goods',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $this->wipAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1030',
        'name' => 'WIP',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    // Setup Journal
    $this->manufacturingJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Manufacturing Operations',
        'short_code' => 'MFG',
        'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Miscellaneous,
    ]);

    // Configure Company Defaults
    $this->company->update([
        'default_manufacturing_journal_id' => $this->manufacturingJournal->id,
        'default_raw_materials_inventory_id' => $this->rmAccount->id,
        'default_finished_goods_inventory_id' => $this->fgAccount->id,
        'default_wip_account_id' => $this->wipAccount->id,
    ]);

    $this->action = app(CreateJournalEntryForManufacturingAction::class);
});

it('creates a journal entry when manufacturing order is completed', function () {
    // Arrange
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'quantity_produced' => 10,
    ]);

    // Clean up any lines created by factory to have full control
    $mo->lines()->delete();

    $line1 = ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 20,
        'unit_cost' => Money::of(100, $this->company->currency->code),
    ]);

    $line2 = ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 10,
        'unit_cost' => Money::of(50, $this->company->currency->code),
    ]);

    // Act
    $journalEntry = $this->action->execute($mo, $this->user);

    // Assert
    expect($journalEntry)->not->toBeNull()
        ->and($mo->refresh()->journal_entry_id)->toBe($journalEntry->id)
        ->and($journalEntry->is_posted)->toBeTrue()
        ->and($journalEntry->lines)->toHaveCount(2); // 1 credit (WIP) + 1 debit (FG)

    // Total Cost = 20 * 100 + 10 * 50 = 2000 + 500 = 2500
    $expectedTotal = 2500.0;

    $fgDebit = $journalEntry->lines()->where('account_id', $this->fgAccount->id)->first();
    expect($fgDebit->debit->getAmount()->toFloat())->toBe($expectedTotal)
        ->and($fgDebit->credit->getAmount()->toFloat())->toBe(0.0)
        ->and($fgDebit->description)->toContain('10.0000 units');

    $wipCreditTotal = $journalEntry->lines()
        ->where('account_id', $this->wipAccount->id)
        ->get()
        ->sum(fn ($line) => $line->credit->getAmount()->toFloat());

    expect($wipCreditTotal)->toBe($expectedTotal);
});

it('throws exception if manufacturing accounts are not configured', function () {
    // Arrange
    $this->company->update([
        'default_wip_account_id' => null,
    ]);

    $mo = ManufacturingOrder::factory()->create(['company_id' => $this->company->id]);

    // Act & Assert
    expect(fn () => $this->action->execute($mo, $this->user))
        ->toThrow(\RuntimeException::class, 'Manufacturing accounts (Finished Goods, WIP, Manufacturing Journal) are not configured for this company.');
});

it('handles multi-currency manufacturing costs correctly by using company currency', function () {
    // Note: The action currently uses the company currency for all entries
    $otherCurrency = Currency::factory()->createSafely(['code' => 'USD']);
    $this->company->update(['currency_id' => $otherCurrency->id]);
    $this->company->refresh();

    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'quantity_produced' => 5,
    ]);

    $mo->lines()->delete();

    ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 10,
        'unit_cost' => Money::of(100, 'USD'),
    ]);

    $journalEntry = $this->action->execute($mo, $this->user);

    $fgDebit = $journalEntry->lines()->where('account_id', $this->fgAccount->id)->first();
    expect($fgDebit->debit->getCurrency()->getCurrencyCode())->toBe('USD')
        ->and($fgDebit->debit->getAmount()->toFloat())->toBe(1000.0);
});

it('handles partial production producing less than ordered quantity', function () {
    // Arrange
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'quantity_to_produce' => 100,
        'quantity_produced' => 50, // Partial
    ]);

    $mo->lines()->delete();

    ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 10, // Consumed for 50 units
        'unit_cost' => Money::of(100, $this->company->currency->code),
    ]);

    // Act
    $journalEntry = $this->action->execute($mo, $this->user);

    // Assert
    $fgDebit = $journalEntry->lines()->where('account_id', $this->fgAccount->id)->first();
    expect($fgDebit->debit->getAmount()->toFloat())->toBe(1000.0)
        ->and($fgDebit->description)->toContain('(50.0000 units)');
});

it('handles multi-component BOM consumption accounting', function () {
    // Arrange
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'quantity_produced' => 1,
    ]);

    $mo->lines()->delete();

    // 3 different components
    ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 1,
        'unit_cost' => Money::of(100, $this->company->currency->code),
    ]);
    ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 2,
        'unit_cost' => Money::of(50, $this->company->currency->code),
    ]);
    ManufacturingOrderLine::factory()->forOrder($mo)->create([
        'quantity_consumed' => 5,
        'unit_cost' => Money::of(10, $this->company->currency->code),
    ]);

    // Act
    $journalEntry = $this->action->execute($mo, $this->user);

    // Assert
    // Total = 1*100 + 2*50 + 5*10 = 100 + 100 + 50 = 250
    // With WIP accounting, we have 1 Debit to FG and 1 Credit to WIP (aggregated)
    expect($journalEntry->lines)->toHaveCount(2);

    $fgDebit = $journalEntry->lines()->where('account_id', $this->fgAccount->id)->first();
    expect($fgDebit->debit->getAmount()->toFloat())->toBe(250.0);

    $wipCredits = $journalEntry->lines()->where('account_id', $this->wipAccount->id)->get();
    // WIP credit is aggregated
    expect($wipCredits)->toHaveCount(1)
        ->and($wipCredits->first()->credit->getAmount()->toFloat())->toBe(250.0);
});
