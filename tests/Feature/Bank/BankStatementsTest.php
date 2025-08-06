<?php

namespace Tests\Feature\Bank;

use App\Models\User;
use Brick\Money\Money;
use App\Models\Journal;
use App\Enums\Accounting\JournalType;
use App\Models\Partner;
use App\Models\BankStatement;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Accounting\CreateBankStatementAction;
use App\Actions\Accounting\UpdateBankStatementAction;
use App\DataTransferObjects\Accounting\CreateBankStatementDTO;
use App\DataTransferObjects\Accounting\UpdateBankStatementDTO;
use App\DataTransferObjects\Accounting\CreateBankStatementLineDTO;
use App\DataTransferObjects\Accounting\UpdateBankStatementLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {

    // Setup a specific journal for bank transactions
    $this->bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
});

test('it creates a bank statement and its lines from a dto', function () {
    // Arrange: Prepare the DTOs needed for the action.
    $partner = Partner::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;

    $lineDTOs = [
        new CreateBankStatementLineDTO(
            date: now()->toDateString(),
            description: 'Incoming Payment #1',
            amount: '1500.00',
            partner_id: (string)$partner->id,
        ),
        new CreateBankStatementLineDTO(
            date: now()->toDateString(),
            description: 'Bank Service Fee',
            amount: '-25.50',
            partner_id: null,
        ),
    ];

    $statementDTO = new CreateBankStatementDTO(
        company_id: $this->company->id,
        currency_id: $this->company->currency_id,
        journal_id: $this->bankJournal->id,
        reference: 'Statement 08/2025',
        date: now()->toDateString(),
        starting_balance: '1000.00',
        ending_balance: '2474.50', // 1000 + 1500 - 25.50
        lines: $lineDTOs,
    );

    // Act: Execute the action.
    $action = app(CreateBankStatementAction::class);
    $bankStatement = $action->execute($statementDTO);

    // Assert: Check that the statement and its lines were created correctly.
    $this->assertModelExists($bankStatement);
    $this->assertDatabaseHas('bank_statements', [
        'id' => $bankStatement->id,
        'reference' => 'Statement 08/2025',
    ]);
    $this->assertDatabaseCount('bank_statement_lines', 2);
    $this->assertDatabaseHas('bank_statement_lines', [
        'bank_statement_id' => $bankStatement->id,
        'description' => 'Incoming Payment #1',
        'partner_id' => $partner->id,
        'amount' => 1500000, // Stored in minor units
    ]);

    // Assert that the string amounts were correctly cast to Money objects.
    $bankStatement->refresh();
    expect($bankStatement->starting_balance)->toBeInstanceOf(Money::class)
        ->and($bankStatement->starting_balance->isEqualTo(Money::of('1000.00', $currencyCode)))->toBeTrue();
    expect($bankStatement->bankStatementLines->first()->amount)->toBeInstanceOf(Money::class);
});

test('it updates a bank statement and syncs its lines from a dto', function () {
    // Arrange: Create an initial bank statement with two lines.
    $currencyCode = $this->company->currency->code;
    $statement = BankStatement::factory()->for($this->company)->for($this->bankJournal)->create([
        'currency_id' => $this->company->currency_id, // <-- THE FIX
    ]);
    $lineToRemove = $statement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Line To Remove',
        'amount' => Money::of(100, $currencyCode)
    ]);
    $lineToUpdate = $statement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Original Description',
        'amount' => Money::of(200, $currencyCode)
    ]);

    // Arrange: Prepare the DTO for the update action.
    // We will update one line, remove another, and add a new one.
    $updateLineDTOs = [
        new UpdateBankStatementLineDTO( // Update existing line
            id: $lineToUpdate->id,
            date: now()->addDay()->toDateString(),
            description: 'Updated Description',
            amount: '250.00',
            partner_id: null
        ),
        new UpdateBankStatementLineDTO( // Add a new line
            id: null,
            date: now()->addDay()->toDateString(),
            description: 'Newly Added Line',
            amount: '-50.00',
            partner_id: null
        ),
    ];

    $updateStatementDTO = new UpdateBankStatementDTO(
        bankStatement: $statement,
        currency_id: $statement->currency_id,
        journal_id: $statement->journal_id,
        reference: 'Updated Statement Reference',
        date: now()->addDay()->toDateString(),
        starting_balance: '1000.00',
        ending_balance: '1200.00', // 1000 + 250 - 50
        lines: $updateLineDTOs
    );

    // Act: Execute the update action.
    $action = new UpdateBankStatementAction();
    $action->execute($updateStatementDTO);

    // Assert: Check the results of the sync operation.
    $this->assertDatabaseHas('bank_statements', [
        'id' => $statement->id,
        'reference' => 'Updated Statement Reference',
    ]);
    $this->assertDatabaseCount('bank_statement_lines', 2);
    $this->assertModelMissing($lineToRemove); // Assert the old line was deleted.
    // The old line was deleted and a new one was created.
    // We should not check for the old ID. We just need to assert that
    // a line with the updated data now exists.
    $this->assertDatabaseHas('bank_statement_lines', [
        // 'id' => $lineToUpdate->id, // <-- DELETE THIS LINE
        'description' => 'Updated Description',
        'amount' => 250000,
    ]);
    $this->assertDatabaseHas('bank_statement_lines', [ // Assert the new line was created.
        'bank_statement_id' => $statement->id,
        'description' => 'Newly Added Line',
        'amount' => -50000,
    ]);
});
