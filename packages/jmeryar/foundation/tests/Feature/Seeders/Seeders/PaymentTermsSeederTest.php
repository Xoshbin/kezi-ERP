<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Foundation\Database\Seeders\PaymentTermsSeeder;
use Jmeryar\Foundation\Models\PaymentTerm;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test company
    $this->company = Company::factory()->create();
});

it('seeds common payment terms for a company', function () {
    // Run the seeder
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    // Verify payment terms were created
    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();

    expect($paymentTerms)->toHaveCount(16); // We defined 16 payment terms

    // Check some specific payment terms (using getTranslation for translatable fields)
    $immediate = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === 'Immediate');
    expect($immediate)->not->toBeNull();
    // The description might be converted to a translation key by the system
    $description = $immediate->getTranslation('description', 'en');
    expect($description)->toBeString();
    expect($description)->not->toBeEmpty();
    expect($immediate->is_active)->toBeTrue();

    $net30 = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === 'Net 30');
    expect($net30)->not->toBeNull();
    expect($net30->getTranslation('description', 'en'))->toBeString();

    $discount = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === '2% 10, Net 30');
    expect($discount)->not->toBeNull();
    expect($discount->getTranslation('description', 'en'))->toBeString();

    $installment = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === '50% Now, 50% in 30 Days');
    expect($installment)->not->toBeNull();
    expect($installment->getTranslation('description', 'en'))->toBeString();
});

it('creates correct payment term lines for immediate payment', function () {
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    $immediate = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === 'Immediate');

    $lines = $immediate->lines;
    expect($lines)->toHaveCount(1);

    $line = $lines->first();
    expect($line->sequence)->toBe(1);
    expect($line->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::Immediate);
    expect($line->days)->toBe(0);
    expect($line->percentage)->toBe(100.0);
    expect($line->discount_percentage)->toBeNull();
    expect($line->discount_days)->toBeNull();
});

it('creates correct payment term lines for net 30', function () {
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    $net30 = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === 'Net 30');

    $lines = $net30->lines;
    expect($lines)->toHaveCount(1);

    $line = $lines->first();
    expect($line->sequence)->toBe(1);
    expect($line->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::Net);
    expect($line->days)->toBe(30);
    expect($line->percentage)->toBe(100.0);
    expect($line->discount_percentage)->toBeNull();
    expect($line->discount_days)->toBeNull();
});

it('creates correct payment term lines for early payment discount', function () {
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    $discount = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === '2% 10, Net 30');

    $lines = $discount->lines;
    expect($lines)->toHaveCount(1);

    $line = $lines->first();
    expect($line->sequence)->toBe(1);
    expect($line->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::Net);
    expect($line->days)->toBe(30);
    expect($line->percentage)->toBe(100.0);
    expect($line->discount_percentage)->toBe(2.0);
    expect($line->discount_days)->toBe(10);
});

it('creates correct payment term lines for installment payments', function () {
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    $installment = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === '50% Now, 50% in 30 Days');

    $lines = $installment->lines->sortBy('sequence');
    expect($lines)->toHaveCount(2);

    // First installment - immediate
    $firstLine = $lines->first();
    expect($firstLine->sequence)->toBe(1);
    expect($firstLine->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::Immediate);
    expect($firstLine->days)->toBe(0);
    expect($firstLine->percentage)->toBe(50.0);

    // Second installment - 30 days
    $secondLine = $lines->last();
    expect($secondLine->sequence)->toBe(2);
    expect($secondLine->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::Net);
    expect($secondLine->days)->toBe(30);
    expect($secondLine->percentage)->toBe(50.0);
});

it('creates correct payment term lines for day of month', function () {
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    $dayOfMonth = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === '15th of Next Month');

    $lines = $dayOfMonth->lines;
    expect($lines)->toHaveCount(1);

    $line = $lines->first();
    expect($line->sequence)->toBe(1);
    expect($line->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::DayOfMonth);
    expect($line->days)->toBe(0);
    expect($line->day_of_month)->toBe(15);
    expect($line->percentage)->toBe(100.0);
});

it('creates correct payment term lines for end of month', function () {
    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    $eom = $paymentTerms->first(fn ($term) => $term->getTranslation('name', 'en') === 'EOM + 15');

    $lines = $eom->lines;
    expect($lines)->toHaveCount(1);

    $line = $lines->first();
    expect($line->sequence)->toBe(1);
    expect($line->type)->toBe(\Jmeryar\Foundation\Enums\PaymentTerms\PaymentTermType::EndOfMonth);
    expect($line->days)->toBe(15);
    expect($line->percentage)->toBe(100.0);
});

it('does not create duplicate payment terms', function () {
    // Run seeder twice
    $seeder = new PaymentTermsSeeder;
    $seeder->run();
    $seeder->run();

    // Should still only have one set of payment terms
    $paymentTerms = PaymentTerm::where('company_id', $this->company->id)->get();
    expect($paymentTerms)->toHaveCount(16);

    // Check that we don't have duplicates
    $immediateTerms = $paymentTerms->filter(fn ($term) => $term->getTranslation('name', 'en') === 'Immediate');
    expect($immediateTerms)->toHaveCount(1);
});

it('creates payment terms for multiple companies', function () {
    $company2 = Company::factory()->create();

    $seeder = new PaymentTermsSeeder;
    $seeder->run();

    // Both companies should have payment terms
    $company1Terms = PaymentTerm::where('company_id', $this->company->id)->count();
    $company2Terms = PaymentTerm::where('company_id', $company2->id)->count();

    expect($company1Terms)->toBe(16);
    expect($company2Terms)->toBe(16);

    // Terms should be separate for each company
    $totalTerms = PaymentTerm::count();
    expect($totalTerms)->toBe(32); // 16 terms × 2 companies
});
