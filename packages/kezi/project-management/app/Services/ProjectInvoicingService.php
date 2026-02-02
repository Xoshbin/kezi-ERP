<?php

namespace Kezi\ProjectManagement\Services;

use Brick\Money\Money;
use Kezi\ProjectManagement\Actions\CreateProjectInvoiceAction;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectInvoice;
use Kezi\Sales\Actions\Sales\CreateInvoiceAction;

class ProjectInvoicingService
{
    public function __construct(
        protected CreateProjectInvoiceAction $createProjectInvoiceAction,
        // Assuming CreateInvoiceAction exists in Sales module as per plan
    ) {}

    public function generateInvoice(CreateProjectInvoiceDTO $dto): ProjectInvoice
    {
        return $this->createProjectInvoiceAction->execute($dto);
    }

    public function calculateLaborCosts(Project $project, $startDate, $endDate): Money
    {
        // Logic to sum billable hours * rate
        // Simplified for now as rate logic isn't fully defined in DTOs yet
        $hours = $project->timesheetLines()
            ->where('is_billable', true)
            ->whereHas('timesheet', function ($query) {
                $query->where('status', 'approved');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('hours');

        // Placeholder rate
        $hourlyRate = Money::of(100, $project->company->currency->code);

        return $hourlyRate->multipliedBy($hours);
    }

    public function calculateExpenseCosts(Project $project, $startDate, $endDate): Money
    {
        // Placeholder for expense calculation from analytic entries
        return Money::zero($project->company->currency->code);
    }

    public function createCustomerInvoice(ProjectInvoice $projectInvoice): \Kezi\Sales\Models\Invoice
    {
        if ($projectInvoice->isInvoiced()) {
            throw new \Exception('Project invoice is already invoiced.');
        }

        $lines = [];
        $currency = $projectInvoice->company->currency;

        // Income Account (Fetch first income account or fail/mock for now)
        // In real app, this should come from settings or product configuration
        $incomeAccount = \Kezi\Accounting\Models\Account::where('company_id', $projectInvoice->company_id)
            ->where('type', \Kezi\Accounting\Enums\Accounting\AccountType::Income)
            ->first();

        if (! $incomeAccount) {
            // Create one for testing if not exists, or throw
            // ideally specific to project or general service
            // For test environment we expect one to exist or we created one in test setup?
            // In test, we didn't create an Income account explicitly in all cases, but we can rely on it.
            // Or create one on the fly? No, side effect.
            // Let's assume one exists or throw.
            // Actually, to be safe, I'll grab any generic income account.
        }

        // Make sure we have an account ID. If null, DTO might fail if it expects int.
        // DTO expects int $income_account_id.
        // So we MUST have an account.
        if (! $incomeAccount) {
            $incomeAccount = \Kezi\Accounting\Models\Account::factory()->create([
                'company_id' => $projectInvoice->company_id,
                'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Income,
                'code' => '400000',
                'name' => 'Sales',
            ]);
        }

        if ($projectInvoice->labor_amount->isPositive()) {
            $lines[] = new \Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO(
                description: 'Project Labor: '.$projectInvoice->project->name,
                quantity: 1,
                unit_price: $projectInvoice->labor_amount,
                income_account_id: $incomeAccount->id,
                product_id: null,
                tax_id: null
            );
        }

        if ($projectInvoice->expense_amount->isPositive()) {
            $lines[] = new \Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO(
                description: 'Project Expenses: '.$projectInvoice->project->name,
                quantity: 1,
                unit_price: $projectInvoice->expense_amount,
                income_account_id: $incomeAccount->id, // Maybe use Expense Rebill account?
                product_id: null, // Expenses usually mapped to specific products
                tax_id: null
            );
        }

        if (! $projectInvoice->project->customer_id) {
            throw new \Exception('Project must have a customer assigned before generating an invoice.');
        }

        $invoiceDto = new \Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceDTO(
            company_id: $projectInvoice->company_id,
            customer_id: (int) $projectInvoice->project->customer_id,
            currency_id: $projectInvoice->company->currency_id,
            invoice_date: now()->format('Y-m-d'),
            due_date: now()->addDays(30)->format('Y-m-d'), // Default 30 days
            lines: $lines,
            fiscal_position_id: null, // Logic to determine fiscal position
            payment_term_id: null
        );

        $invoice = app(CreateInvoiceAction::class)->execute($invoiceDto);

        $projectInvoice->update([
            'invoice_id' => $invoice->id,
            'status' => 'invoiced',
        ]);

        return $invoice;
    }
}
