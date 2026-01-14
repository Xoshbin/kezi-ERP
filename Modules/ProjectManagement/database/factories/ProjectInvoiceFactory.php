<?php

namespace Modules\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ProjectManagement\Models\ProjectInvoice;

class ProjectInvoiceFactory extends Factory
{
    protected $model = ProjectInvoice::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'project_id' => \Modules\ProjectManagement\Models\Project::factory(),
            'invoice_date' => now(),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'labor_amount' => 0,
            'expense_amount' => 0,
            'total_amount' => 0,
        ];
    }
}
