<?php

namespace Tests\Feature;

use App\Enums\Accounting\TaxType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Tax;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class TranslatableSelectModalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        Filament::setTenant($this->company);
    }

    /** @test */
    public function translatable_select_for_model_does_not_try_to_establish_relationships()
    {
        // Create a tax account for the test
        $taxAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'VAT Payable'],
            'code' => 'VAT-001',
        ]);

        // Create a TranslatableSelect component using forModel (as used in createOptionForm)
        $component = TranslatableSelect::forModel('tax_id', Tax::class, 'name');

        // This should not throw an error about null relationships
        $this->assertInstanceOf(TranslatableSelect::class, $component);
        $this->assertEquals(Tax::class, $component->getModelClass());
        $this->assertNull($component->getRelationshipName());

        // The getRelationship method should return null for forModel usage
        $this->assertNull($component->getRelationship());
    }

    /** @test */
    public function translatable_select_for_model_can_get_search_results()
    {
        // Create a tax account and some taxes
        $taxAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'VAT Payable'],
            'code' => 'VAT-001',
        ]);

        $tax1 = Tax::factory()->create([
            'company_id' => $this->company->id,
            'tax_account_id' => $taxAccount->id,
            'name' => ['en' => 'VAT 10%', 'ar' => 'ضريبة القيمة المضافة 10%'],
            'rate' => 0.10,
            'type' => TaxType::Both,
            'is_active' => true,
        ]);

        $tax2 = Tax::factory()->create([
            'company_id' => $this->company->id,
            'tax_account_id' => $taxAccount->id,
            'name' => ['en' => 'Tax Exempt', 'ar' => 'معفي من الضريبة'],
            'rate' => 0.00,
            'type' => TaxType::Both,
            'is_active' => true,
        ]);

        // Create a TranslatableSelect component using forModel
        $component = TranslatableSelect::forModel('tax_id', Tax::class, 'name');

        // Test that we can get search results without errors
        $searchResults = $component->getSearchResultsClosure()('VAT');

        // Should return results containing the VAT tax
        $this->assertIsArray($searchResults);
        $this->assertArrayHasKey($tax1->id, $searchResults);
        $this->assertStringContainsString('VAT 10%', $searchResults[$tax1->id]);
    }

    /** @test */
    public function translatable_select_for_model_can_get_option_labels()
    {
        // Create a tax account and a tax
        $taxAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'VAT Payable'],
            'code' => 'VAT-001',
        ]);

        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'tax_account_id' => $taxAccount->id,
            'name' => ['en' => 'VAT 10%', 'ar' => 'ضريبة القيمة المضافة 10%'],
            'rate' => 0.10,
            'type' => TaxType::Both,
            'is_active' => true,
        ]);

        // Create a TranslatableSelect component using forModel
        $component = TranslatableSelect::forModel('tax_id', Tax::class, 'name');

        // Test that we can get option labels without errors
        $optionLabel = $component->getOptionLabelClosure()($tax->id);

        // Should return the tax name
        $this->assertIsString($optionLabel);
        $this->assertStringContainsString('VAT 10%', $optionLabel);
    }
}
