<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\AccountGroup;

/**
 * @extends Factory<AccountGroup>
 */
class AccountGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = $this->faker->numberBetween(1, 5);
        $suffix = $this->faker->numberBetween(10, 50);

        return [
            'company_id' => Company::factory(),
            'code_prefix_start' => $prefix.$suffix,
            'code_prefix_end' => $prefix.$suffix.'99',
            'name' => ['en' => $this->faker->words(2, true).' Group'],
            'level' => 0,
        ];
    }

    /**
     * Indicate that the group is a level 1 sub-group.
     */
    public function level1(): Factory
    {
        return $this->state(fn (array $attributes) => ['level' => 1]);
    }

    /**
     * Indicate that the group is a level 2 sub-group.
     */
    public function level2(): Factory
    {
        return $this->state(fn (array $attributes) => ['level' => 2]);
    }

    /**
     * Create an Assets root group.
     */
    public function assets(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'code_prefix_start' => '1',
            'code_prefix_end' => '199999',
            'name' => ['en' => 'Assets', 'ar' => 'الأصول', 'ckb' => 'سامانەکان'],
            'level' => 0,
        ]);
    }

    /**
     * Create a Liabilities root group.
     */
    public function liabilities(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'code_prefix_start' => '2',
            'code_prefix_end' => '299999',
            'name' => ['en' => 'Liabilities', 'ar' => 'الالتزامات', 'ckb' => 'قەرزەکان'],
            'level' => 0,
        ]);
    }

    /**
     * Create an Equity root group.
     */
    public function equity(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'code_prefix_start' => '3',
            'code_prefix_end' => '399999',
            'name' => ['en' => 'Equity', 'ar' => 'حقوق الملكية', 'ckb' => 'سەرمایە'],
            'level' => 0,
        ]);
    }

    /**
     * Create an Income root group.
     */
    public function income(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'code_prefix_start' => '4',
            'code_prefix_end' => '499999',
            'name' => ['en' => 'Income', 'ar' => 'الإيرادات', 'ckb' => 'داهات'],
            'level' => 0,
        ]);
    }

    /**
     * Create an Expenses root group.
     */
    public function expenses(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'code_prefix_start' => '5',
            'code_prefix_end' => '599999',
            'name' => ['en' => 'Expenses', 'ar' => 'المصروفات', 'ckb' => 'خەرجییەکان'],
            'level' => 0,
        ]);
    }
}
