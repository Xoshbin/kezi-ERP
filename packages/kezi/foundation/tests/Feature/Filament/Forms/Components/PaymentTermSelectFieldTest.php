<?php

namespace Kezi\Foundation\Tests\Feature\Filament\Forms\Components;

/**
 * @property-read \App\Models\Company $company
 */
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Filament\Forms\Components\PaymentTermSelectField;
use Kezi\Foundation\Models\PaymentTerm;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $test */
    $test = $this;
    $test->setupWithConfiguredCompany();
});

describe('PaymentTermSelectField', function () {
    it('can extract schema', function () {
        $field = PaymentTermSelectField::make('payment_term_id');

        expect($field->getName())->toBe('payment_term_id')
            ->and($field->getLabel())->toBe(__('foundation::payment_term.label'));
    });

    it('has create option form with correct fields', function () {
        $field = PaymentTermSelectField::make('payment_term_id');

        $mockHasForms = new class extends \Livewire\Component implements \Filament\Forms\Contracts\HasForms
        {
            use \Filament\Forms\Concerns\InteractsWithForms;

            public function render(): string
            {
                return '<div></div>';
            }
        };

        $schema = \Filament\Schemas\Schema::make($mockHasForms);
        $components = $field->getCreateOptionActionForm($schema);

        expect($components)->toBeArray()
            ->and($components)->not->toBeEmpty();
    });

    it('can create payment term using createOptionUsing with tenant company_id', function () {
        $field = PaymentTermSelectField::make('payment_term_id');
        $data = [
            'name' => 'Test Payment Term',
            'code' => 'TPT001',
        ];

        /** @var \Closure(array<string, mixed>): int $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();

        expect($id)->toBeInt();
        $this->assertDatabaseHas('payment_terms', [
            'id' => $id,
            'name' => json_encode(['en' => 'Test Payment Term']),
            'company_id' => $company->id,
        ]);
    });

    it('scopes payment terms to current company', function () {
        /** @var \App\Models\Company $otherCompany */
        $otherCompany = \App\Models\Company::factory()->create();

        PaymentTerm::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => ['en' => 'Other Payment Term'],
        ]);

        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();

        PaymentTerm::factory()->create([
            'company_id' => $company->id,
            'name' => ['en' => 'My Payment Term'],
        ]);

        $field = PaymentTermSelectField::make('payment_term_id');
        $options = collect($field->getOptions());

        expect($options->values())->toContain('My Payment Term')
            ->and($options->values())->not->toContain('Other Payment Term');
    });
});
