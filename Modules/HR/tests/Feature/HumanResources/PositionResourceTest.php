<?php

namespace Modules\HR\Tests\Feature\HumanResources;

use App\Filament\Clusters\HumanResources\Resources\Positions\Pages\CreatePosition;
use App\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;
use App\Models\Position;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

class PositionResourceTest extends TestCase
{
    use RefreshDatabase, WithConfiguredCompany;

    protected $company;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupWithConfiguredCompany();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_the_list_page()
    {
        $this->get(PositionResource::getUrl('index'))->assertSuccessful();
    }

    /** @test */
    public function it_can_render_the_create_page()
    {
        $this->get(PositionResource::getUrl('create'))->assertSuccessful();
    }

    /** @test */
    public function it_can_create_position_with_money_fields()
    {
        $positionData = [
            'company_id' => $this->company->id,
            'title' => ['en' => 'Software Developer', 'ku' => 'گەشەپێدەری نەرمەکاڵا'],
            'description' => 'A software developer position',
            'employment_type' => 'full_time',
            'level' => 'mid',
            'currency_id' => $this->company->currency->id,
            'min_salary' => 800000,
            'max_salary' => 1200000,
            'is_active' => true,
        ];

        // Create position directly to test the business logic
        $position = Position::create($positionData);

        $this->assertDatabaseHas('positions', [
            'company_id' => $this->company->id,
            'title->en' => 'Software Developer',
            'title->ku' => 'گەشەپێدەری نەرمەکاڵا',
            'description' => 'A software developer position',
            'employment_type' => 'full_time',
            'level' => 'mid',
            'currency_id' => $this->company->currency->id,
            'is_active' => true,
        ]);

        // Verify Money objects are properly cast
        $this->assertInstanceOf(Money::class, $position->min_salary);
        $this->assertInstanceOf(Money::class, $position->max_salary);
        $this->assertTrue($position->min_salary->isEqualTo(Money::of(800000, $this->company->currency->code)));
        $this->assertTrue($position->max_salary->isEqualTo(Money::of(1200000, $this->company->currency->code)));
    }

    /** @test */
    public function it_can_render_the_edit_page()
    {
        $position = Position::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency->id,
        ]);

        $this->get(PositionResource::getUrl('edit', ['record' => $position]))
            ->assertSuccessful();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        livewire(CreatePosition::class)
            ->fillForm([
                'title' => null,
                'employment_type' => null,
                'level' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'required',
                'employment_type' => 'required',
                'level' => 'required',
            ]);
    }
}
