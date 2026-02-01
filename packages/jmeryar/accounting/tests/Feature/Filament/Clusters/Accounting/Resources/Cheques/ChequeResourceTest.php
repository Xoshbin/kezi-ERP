<?php

namespace Jmeryar\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\Cheques;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages\ListCheques;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Enums\Cheques\ChequeType;
use Jmeryar\Payment\Models\Cheque;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->user->refresh();

    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    // Setup default journal and accounts
    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '101000',
    ]);

    $this->journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'default_debit_account_id' => $this->bankAccount->id,
        'default_credit_account_id' => $this->bankAccount->id,
    ]);
});

it('can render the list page', function () {
    $cheques = Cheque::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(ListCheques::class)
        ->assertOk()
        ->assertCanSeeTableRecords($cheques);
});

it('can filter cheques by status', function () {
    $draftCheque = Cheque::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
        'status' => ChequeStatus::Draft,
    ]);

    $clearedCheque = Cheque::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
        'status' => ChequeStatus::Cleared,
    ]);

    livewire(ListCheques::class)
        ->filterTable('status', ChequeStatus::Draft->value)
        ->assertCanSeeTableRecords([$draftCheque])
        ->assertCanNotSeeTableRecords([$clearedCheque]);
});

it('can filter cheques by type', function () {
    $payableCheque = Cheque::factory()->payable()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $receivableCheque = Cheque::factory()->receivable()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(ListCheques::class)
        ->filterTable('type', ChequeType::Payable->value)
        ->assertCanSeeTableRecords([$payableCheque])
        ->assertCanNotSeeTableRecords([$receivableCheque]);
});

it('can search cheques by cheque number', function () {
    $cheque1 = Cheque::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
        'cheque_number' => 'CHQ-001',
    ]);

    $cheque2 = Cheque::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->journal->id,
        'currency_id' => $this->company->currency_id,
        'cheque_number' => 'CHQ-002',
    ]);

    livewire(ListCheques::class)
        ->searchTable('CHQ-001')
        ->assertCanSeeTableRecords([$cheque1])
        ->assertCanNotSeeTableRecords([$cheque2]);
});
