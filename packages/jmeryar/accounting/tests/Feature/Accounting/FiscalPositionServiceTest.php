<?php

namespace Jmeryar\Accounting\Tests\Feature\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\FiscalPosition;
use Jmeryar\Accounting\Models\FiscalPositionAccountMapping;
use Jmeryar\Accounting\Models\FiscalPositionTaxMapping;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Accounting\Services\Accounting\FiscalPositionService;
use Jmeryar\Foundation\Models\Partner;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->service = app(FiscalPositionService::class);
});

describe('FiscalPositionService', function () {

    describe('getFiscalPositionForPartner', function () {

        it('returns explicit fiscal position if set on partner', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create(['name' => 'Explicit FP']);
            $partner = Partner::factory()->for($this->company)->create([
                'fiscal_position_id' => $fp->id,
            ]);

            $result = $this->service->getFiscalPositionForPartner($partner);

            expect($result->id)->toBe($fp->id);
        });

        it('returns matching auto-apply fiscal position by country', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create([
                'name' => 'Iraq FP',
                'country' => 'IQ',
                'auto_apply' => true,
            ]);

            $partner = Partner::factory()->for($this->company)->create([
                'country' => 'IQ',
                'fiscal_position_id' => null,
            ]);

            $result = $this->service->getFiscalPositionForPartner($partner);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($fp->id);
        });

        it('returns matching auto-apply fiscal position by zip range', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create([
                'name' => 'Zip FP',
                'zip_from' => '10000',
                'zip_to' => '20000',
                'auto_apply' => true,
                'country' => null,
            ]);

            $partner = Partner::factory()->for($this->company)->create([
                'zip_code' => '15000',
                'fiscal_position_id' => null,
            ]);

            $result = $this->service->getFiscalPositionForPartner($partner);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($fp->id);
        });

        it('returns matching auto-apply fiscal position by VAT requirement', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create([
                'name' => 'VAT FP',
                'vat_required' => true,
                'auto_apply' => true,
                'country' => null,
            ]);

            $partnerWithVat = Partner::factory()->for($this->company)->create([
                'fiscal_position_id' => null,
                'tax_id' => '123456789',
            ]);

            $partnerWithoutVat = Partner::factory()->for($this->company)->create([
                'fiscal_position_id' => null,
                'tax_id' => null,
            ]);

            expect($this->service->getFiscalPositionForPartner($partnerWithVat))->not->toBeNull()
                ->and($this->service->getFiscalPositionForPartner($partnerWithoutVat))->toBeNull();
        });

        it('prioritizes specific country match over generic one', function () {
            $genericFp = FiscalPosition::factory()->for($this->company)->create([
                'name' => 'Generic FP',
                'auto_apply' => true,
                'country' => null,
            ]);

            $countryFp = FiscalPosition::factory()->for($this->company)->create([
                'name' => 'Specific FP',
                'auto_apply' => true,
                'country' => 'IQ',
            ]);

            $partner = Partner::factory()->for($this->company)->create([
                'country' => 'IQ',
                'fiscal_position_id' => null,
            ]);

            $result = $this->service->getFiscalPositionForPartner($partner);

            expect($result->id)->toBe($countryFp->id);
        });
    });

    describe('Tax Mapping', function () {
        it('maps original tax to mapped tax based on fiscal position', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create();
            $originalTax = Tax::factory()->for($this->company)->create(['name' => '15% VAT']);
            $mappedTax = Tax::factory()->for($this->company)->create(['name' => '0% Export']);

            FiscalPositionTaxMapping::factory()->create([
                'company_id' => $this->company->id,
                'fiscal_position_id' => $fp->id,
                'original_tax_id' => $originalTax->id,
                'mapped_tax_id' => $mappedTax->id,
            ]);

            $result = $this->service->mapTax($fp, $originalTax);

            expect($result->id)->toBe($mappedTax->id);
        });

        it('returns original tax if no mapping exists', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create();
            $originalTax = Tax::factory()->for($this->company)->create();

            $result = $this->service->mapTax($fp, $originalTax);

            expect($result->id)->toBe($originalTax->id);
        });

        it('returns original tax if fiscal position is null', function () {
            $originalTax = Tax::factory()->for($this->company)->create();

            $result = $this->service->mapTax(null, $originalTax);

            expect($result->id)->toBe($originalTax->id);
        });
    });

    describe('Account Mapping', function () {
        it('maps original account to mapped account based on fiscal position', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create();
            $originalAccount = Account::factory()->for($this->company)->create(['name' => 'Sales Local']);
            $mappedAccount = Account::factory()->for($this->company)->create(['name' => 'Sales Export']);

            FiscalPositionAccountMapping::factory()->create([
                'company_id' => $this->company->id,
                'fiscal_position_id' => $fp->id,
                'original_account_id' => $originalAccount->id,
                'mapped_account_id' => $mappedAccount->id,
            ]);

            $result = $this->service->mapAccount($fp, $originalAccount);

            expect($result->id)->toBe($mappedAccount->id);
        });

        it('returns original account if no mapping exists', function () {
            $fp = FiscalPosition::factory()->for($this->company)->create();
            $originalAccount = Account::factory()->for($this->company)->create();

            $result = $this->service->mapAccount($fp, $originalAccount);

            expect($result->id)->toBe($originalAccount->id);
        });

        it('returns original account if fiscal position is null', function () {
            $originalAccount = Account::factory()->for($this->company)->create();

            $result = $this->service->mapAccount(null, $originalAccount);

            expect($result->id)->toBe($originalAccount->id);
        });
    });
});
