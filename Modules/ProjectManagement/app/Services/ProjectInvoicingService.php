<?php

namespace Modules\ProjectManagement\Services;

use Brick\Money\Money;
use Modules\ProjectManagement\Actions\CreateProjectInvoiceAction;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectInvoice;
use Modules\Sales\Actions\CreateInvoiceAction;
use Modules\Sales\DataTransferObjects\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\InvoiceLineDTO;

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

    public function createCustomerInvoice(ProjectInvoice $projectInvoice): void
    {
        if ($projectInvoice->isInvoiced()) {
            return;
        }

        // Logic to convert ProjectInvoice to Sales Invoice
        // This requires Sales module CreateInvoiceAction instantiation or injection
        // For now, we'll mark it as a TODO or use a placeholder if the action isn't strictly available in context

        // $invoiceLine = new InvoiceLineDTO(...);
        // $invoiceDto = new CreateInvoiceDTO(...);
        // $invoice = app(CreateInvoiceAction::class)->execute($invoiceDto);

        // $projectInvoice->update([
        //     'invoice_id' => $invoice->id,
        //     'status' => 'invoiced'
        // ]);
    }
}
