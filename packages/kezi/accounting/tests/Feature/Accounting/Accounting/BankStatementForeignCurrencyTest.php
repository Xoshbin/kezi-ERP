<?php

use Brick\Money\Money;
use Kezi\Accounting\Actions\Accounting\CreateBankStatementAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateBankStatementDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateBankStatementLineDTO;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\BankStatement;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;

uses(\Tests\TestCase::class, WithConfiguredCompany::class);

describe('Bank Statement Foreign Currency Transactions', function () {
    beforeEach(function () {
        // Create USD currency for foreign currency tests
        $this->usdCurrency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'is_active' => true,
                'decimal_places' => 2,
            ]
        );

        // Create EUR currency for additional tests
        $this->eurCurrency = Currency::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name' => ['en' => 'Euro', 'ckb' => 'یۆرۆ', 'ar' => 'يورو'],
                'symbol' => '€',
                'is_active' => true,
                'decimal_places' => 2,
            ]
        );

        // Create a USD bank journal for testing
        $this->usdBankJournal = Journal::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->create([
                'type' => JournalType::Bank,
                'name' => ['en' => 'Bank (USD)', 'ckb' => 'بانک (USD)', 'ar' => 'بنك (USD)'],
            ]);
    });

    it('correctly records a foreign currency transaction following the specification', function () {
        // Arrange: Create a main bank statement in Iraqi Dinars (IQD)
        $iqdCurrency = $this->company->currency; // Company default is IQD

        // Transaction was for $100.00 USD, which converted to 148,500 IQD.
        // We store amounts in minor units.
        $lineData = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'Payment to AWS',
            amount: Money::of(-148.5, 'IQD'), // -148.5 IQD (negative for outbound)
            partner_id: null,
            foreign_currency_id: $this->usdCurrency->id,
            amount_in_foreign_currency: Money::of(-100, 'USD') // -$100.00 (negative for outbound)
        );

        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: $this->company->id,
            currency_id: $iqdCurrency->id,
            journal_id: $this->company->journals()->where('type', JournalType::Bank)->first()->id,
            reference: 'BS-2025-001',
            date: now()->format('Y-m-d'),
            starting_balance: Money::of(1000, 'IQD'), // 1,000 IQD
            ending_balance: Money::of(851.5, 'IQD'), // 851.5 IQD
            lines: [$lineData]
        );

        // Action: Execute our business logic
        $bankStatement = app(CreateBankStatementAction::class)->execute($bankStatementDTO);

        // Assert: Verify that the statement was created correctly
        expect($bankStatement)->toBeInstanceOf(BankStatement::class);
        expect($bankStatement->currency->code)->toBe('IQD');
        expect($bankStatement->starting_balance->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($bankStatement->ending_balance->getCurrency()->getCurrencyCode())->toBe('IQD');

        // Assert: Verify that the line was created with both currency details
        $line = $bankStatement->bankStatementLines->first();
        expect($line)->not->toBeNull();

        // Main amount should be in IQD (statement currency)
        expect($line->amount->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($line->amount->getMinorAmount()->toInt())->toBe(-148500); // -148.5 IQD in minor units

        // Foreign currency details should be preserved
        expect($line->foreign_currency_id)->toBe($this->usdCurrency->id);
        expect($line->amount_in_foreign_currency)->not->toBeNull();
        expect($line->amount_in_foreign_currency->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($line->amount_in_foreign_currency->getMinorAmount()->toInt())->toBe(-10000); // -$100.00 in cents

        // Assert that the relationships are correctly loaded
        expect($line->bankStatement->currency->code)->toBe('IQD');
        expect($line->foreignCurrency->code)->toBe('USD');
    });

    it('validates that foreign currency and foreign amount must be provided together', function () {
        // Test case 1: Foreign currency provided but no foreign amount
        expect(fn () => new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'Test transaction',
            amount: Money::of(100, 'IQD'),
            partner_id: null,
            foreign_currency_id: $this->usdCurrency->id,
            amount_in_foreign_currency: null // Missing foreign amount
        ))->toThrow(\InvalidArgumentException::class, 'Foreign amount is required when a foreign currency is specified.');

        // Test case 2: Foreign amount provided but no foreign currency
        expect(fn () => new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'Test transaction',
            amount: Money::of(100, 'IQD'),
            partner_id: null,
            foreign_currency_id: null, // Missing foreign currency
            amount_in_foreign_currency: Money::of(100, 'USD')
        ))->toThrow(\InvalidArgumentException::class, 'Foreign currency is required when a foreign amount is specified.');
    });

    it('allows transactions without foreign currency (same currency as statement)', function () {
        // This should work fine - no foreign currency fields
        $lineData = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'Local IQD transaction',
            amount: Money::of(50, 'IQD'),
            partner_id: null,
            foreign_currency_id: null,
            amount_in_foreign_currency: null
        );

        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: $this->company->id,
            currency_id: $this->company->currency_id,
            journal_id: $this->company->journals()->where('type', JournalType::Bank)->first()->id,
            reference: 'BS-2025-002',
            date: now()->format('Y-m-d'),
            starting_balance: Money::of(1000, 'IQD'),
            ending_balance: Money::of(1050, 'IQD'),
            lines: [$lineData]
        );

        $bankStatement = app(CreateBankStatementAction::class)->execute($bankStatementDTO);
        $line = $bankStatement->bankStatementLines->first();

        expect($line->foreign_currency_id)->toBeNull();
        expect($line->amount_in_foreign_currency)->toBeNull();
        expect($line->amount->getCurrency()->getCurrencyCode())->toBe('IQD');
    });

    it('handles multiple foreign currency transactions correctly', function () {
        // Create multiple lines with different foreign currencies
        $usdLine = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'USD Payment',
            amount: Money::of(-146, 'IQD'), // $100 at 1.46 rate
            partner_id: null,
            foreign_currency_id: $this->usdCurrency->id,
            amount_in_foreign_currency: Money::of(-100, 'USD') // -$100.00
        );

        $eurLine = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'EUR Payment',
            amount: Money::of(-175, 'IQD'), // €100 at 1.75 rate
            partner_id: null,
            foreign_currency_id: $this->eurCurrency->id,
            amount_in_foreign_currency: Money::of(-100, 'EUR') // -€100.00
        );

        $localLine = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'Local IQD transaction',
            amount: Money::of(50, 'IQD'),
            partner_id: null,
            foreign_currency_id: null,
            amount_in_foreign_currency: null
        );

        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: $this->company->id,
            currency_id: $this->company->currency_id,
            journal_id: $this->company->journals()->where('type', JournalType::Bank)->first()->id,
            reference: 'BS-2025-003',
            date: now()->format('Y-m-d'),
            starting_balance: Money::of(1000, 'IQD'),
            ending_balance: Money::of(729, 'IQD'), // 1,000 - 146 - 175 + 50
            lines: [$usdLine, $eurLine, $localLine]
        );

        $bankStatement = app(CreateBankStatementAction::class)->execute($bankStatementDTO);

        expect($bankStatement->bankStatementLines)->toHaveCount(3);

        $lines = $bankStatement->bankStatementLines;

        // USD line
        $usdStatementLine = $lines->where('description', 'USD Payment')->first();
        expect($usdStatementLine->foreign_currency_id)->toBe($this->usdCurrency->id);
        expect($usdStatementLine->amount_in_foreign_currency->getCurrency()->getCurrencyCode())->toBe('USD');

        // EUR line
        $eurStatementLine = $lines->where('description', 'EUR Payment')->first();
        expect($eurStatementLine->foreign_currency_id)->toBe($this->eurCurrency->id);
        expect($eurStatementLine->amount_in_foreign_currency->getCurrency()->getCurrencyCode())->toBe('EUR');

        // Local line
        $localStatementLine = $lines->where('description', 'Local IQD transaction')->first();
        expect($localStatementLine->foreign_currency_id)->toBeNull();
        expect($localStatementLine->amount_in_foreign_currency)->toBeNull();
    });

    it('correctly handles USD bank statement with foreign currency transactions', function () {
        // Create a USD bank statement (foreign currency bank account)
        $eurLine = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'EUR transaction on USD account',
            amount: Money::of(-115, 'USD'), // €100 converted to $115 at 1.15 rate
            partner_id: null,
            foreign_currency_id: $this->eurCurrency->id,
            amount_in_foreign_currency: Money::of(-100, 'EUR') // -€100.00
        );

        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: $this->company->id,
            currency_id: $this->usdCurrency->id, // USD bank account
            journal_id: $this->usdBankJournal->id,
            reference: 'BS-USD-001',
            date: now()->format('Y-m-d'),
            starting_balance: Money::of(1000, 'USD'), // $1,000.00
            ending_balance: Money::of(885, 'USD'), // $885.00
            lines: [$eurLine]
        );

        $bankStatement = app(CreateBankStatementAction::class)->execute($bankStatementDTO);
        $line = $bankStatement->bankStatementLines->first();

        // Statement should be in USD
        expect($bankStatement->currency->code)->toBe('USD');
        expect($line->amount->getCurrency()->getCurrencyCode())->toBe('USD');

        // Foreign currency should be EUR
        expect($line->foreignCurrency->code)->toBe('EUR');
        expect($line->amount_in_foreign_currency->getCurrency()->getCurrencyCode())->toBe('EUR');
    });

    it('validates business rules in DTO construction', function () {
        // Valid construction should work
        $validDTO = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'Valid foreign currency transaction',
            amount: Money::of(100, 'IQD'),
            partner_id: null,
            foreign_currency_id: $this->usdCurrency->id,
            amount_in_foreign_currency: Money::of(100, 'USD')
        );

        expect($validDTO->foreign_currency_id)->toBe($this->usdCurrency->id);
        expect($validDTO->amount_in_foreign_currency->getCurrency()->getCurrencyCode())->toBe('USD');
    });

    it('ensures amount field always uses statement currency regardless of foreign currency', function () {
        // Create a USD bank statement with EUR foreign transaction
        $eurLine = new CreateBankStatementLineDTO(
            date: now()->format('Y-m-d'),
            description: 'EUR payment from USD account',
            amount: Money::of(-115, 'USD'), // This MUST be in USD (statement currency)
            partner_id: null,
            foreign_currency_id: $this->eurCurrency->id,
            amount_in_foreign_currency: Money::of(-100, 'EUR') // This is in EUR (foreign currency)
        );

        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: $this->company->id,
            currency_id: $this->usdCurrency->id, // Statement is in USD
            journal_id: $this->usdBankJournal->id,
            reference: 'BS-USD-002',
            date: now()->format('Y-m-d'),
            starting_balance: Money::of(1000, 'USD'),
            ending_balance: Money::of(885, 'USD'),
            lines: [$eurLine]
        );

        $bankStatement = app(CreateBankStatementAction::class)->execute($bankStatementDTO);
        $line = $bankStatement->bankStatementLines->first();

        // The main amount MUST always be in the statement currency (USD)
        expect($line->amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($line->amount->getMinorAmount()->toInt())->toBe(-11500); // -$115.00 in cents

        // The foreign amount should be in the foreign currency (EUR)
        expect($line->amount_in_foreign_currency->getCurrency()->getCurrencyCode())->toBe('EUR');
        expect($line->amount_in_foreign_currency->getMinorAmount()->toInt())->toBe(-10000); // -€100.00 in cents

        // Verify the statement balance is calculated using the main amount (USD)
        expect($bankStatement->starting_balance->getMinorAmount()->toInt())->toBe(100000); // $1000.00
        expect($bankStatement->ending_balance->getMinorAmount()->toInt())->toBe(88500); // $885.00
    });
});
