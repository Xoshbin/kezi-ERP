<?php

namespace App\Services;

use App\DataTransferObjects\Sales\CreateRecurringInvoiceTemplateDTO;
use App\DataTransferObjects\Sales\CreateRecurringInvoiceDTO;
use App\DataTransferObjects\Sales\RecurringInvoiceLineDTO;
use App\Enums\RecurringInvoice\RecurringFrequency;
use App\Enums\RecurringInvoice\RecurringStatus;
use App\Models\Company;
use App\Models\Partner;
use App\Models\RecurringInvoiceTemplate;
use App\Models\User;
use App\Services\Accounting\LockDateService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringInterCompanyService
{
    public function __construct(
        protected LockDateService $lockDateService,
    ) {}

    /**
     * Create a new recurring invoice template.
     */
    public function createTemplate(CreateRecurringInvoiceTemplateDTO $dto): RecurringInvoiceTemplate
    {
        return DB::transaction(function () use ($dto) {
            // Validate inter-company relationship
            $this->validateInterCompanyRelationship($dto->company_id, $dto->target_company_id);

            // Calculate initial next run date
            $nextRunDate = $this->calculateInitialNextRunDate($dto->start_date, $dto->frequency, $dto->day_of_month);

            // Prepare template data
            $templateData = [
                'lines' => array_map(function (RecurringInvoiceLineDTO $line) {
                    return [
                        'description' => $line->description,
                        'quantity' => $line->quantity,
                        'unit_price' => [
                            'amount' => $line->unit_price->getMinorAmount()->toInt(),
                            'currency' => $line->unit_price->getCurrency()->getCurrencyCode(),
                        ],
                        'product_id' => $line->product_id,
                        'tax_id' => $line->tax_id,
                    ];
                }, $dto->lines),
            ];

            $template = RecurringInvoiceTemplate::create([
                'company_id' => $dto->company_id,
                'target_company_id' => $dto->target_company_id,
                'name' => $dto->name,
                'description' => $dto->description,
                'reference_prefix' => $dto->reference_prefix,
                'frequency' => $dto->frequency,
                'start_date' => $dto->start_date,
                'end_date' => $dto->end_date,
                'next_run_date' => $nextRunDate,
                'day_of_month' => $dto->day_of_month,
                'month_of_quarter' => $dto->month_of_quarter,
                'currency_id' => $dto->currency_id,
                'income_account_id' => $dto->income_account_id,
                'expense_account_id' => $dto->expense_account_id,
                'tax_id' => $dto->tax_id,
                'template_data' => $templateData,
                'created_by_user_id' => $dto->created_by_user_id,
            ]);

            Log::info("Created recurring invoice template {$template->id} for company {$dto->company_id} targeting company {$dto->target_company_id}");

            return $template;
        });
    }

    /**
     * Get all templates that are due for generation.
     */
    public function getDueTemplates(): Collection
    {
        return RecurringInvoiceTemplate::where('is_active', true)
            ->where('status', RecurringStatus::Active)
            ->where('next_run_date', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('next_run_date', '<=', DB::raw('end_date'));
            })
            ->with(['company', 'targetCompany', 'currency'])
            ->get();
    }

    /**
     * Generate invoices for a specific template.
     */
    public function generateFromTemplate(RecurringInvoiceTemplate $template, User $user): CreateRecurringInvoiceDTO
    {
        // Validate template is ready for generation
        if (!$template->isDue()) {
            throw new \InvalidArgumentException("Template {$template->id} is not due for generation");
        }

        // Enforce lock date for both companies
        $this->lockDateService->enforce($template->company, $template->next_run_date);
        $this->lockDateService->enforce($template->targetCompany, $template->next_run_date);

        // Calculate due date (30 days from invoice date by default)
        $invoiceDate = Carbon::parse($template->next_run_date);
        $dueDate = $invoiceDate->copy()->addDays(30);

        // Generate reference
        $reference = $this->generateReference($template, $invoiceDate);

        // Convert template data to DTOs
        $lines = array_map(function ($lineData) use ($template) {
            return new RecurringInvoiceLineDTO(
                description: $lineData['description'],
                quantity: $lineData['quantity'],
                unit_price: \Brick\Money\Money::ofMinor(
                    $lineData['unit_price']['amount'],
                    $lineData['unit_price']['currency']
                ),
                product_id: $lineData['product_id'] ?? null,
                tax_id: $lineData['tax_id'] ?? null,
            );
        }, $template->template_data['lines']);

        return new CreateRecurringInvoiceDTO(
            recurring_template_id: $template->id,
            company_id: $template->company_id,
            target_company_id: $template->target_company_id,
            currency_id: $template->currency_id,
            invoice_date: $invoiceDate,
            due_date: $dueDate,
            lines: $lines,
            reference: $reference,
            income_account_id: $template->income_account_id,
            expense_account_id: $template->expense_account_id,
            tax_id: $template->tax_id,
            created_by_user_id: $user->id,
        );
    }

    /**
     * Mark template as completed after generation.
     */
    public function updateTemplateAfterGeneration(RecurringInvoiceTemplate $template): void
    {
        $template->updateAfterGeneration();

        if ($template->shouldComplete()) {
            $template->update(['status' => RecurringStatus::Completed]);
            Log::info("Marked recurring template {$template->id} as completed");
        }
    }

    /**
     * Pause a recurring template.
     */
    public function pauseTemplate(RecurringInvoiceTemplate $template): void
    {
        $template->update(['status' => RecurringStatus::Paused]);
        Log::info("Paused recurring template {$template->id}");
    }

    /**
     * Resume a paused template.
     */
    public function resumeTemplate(RecurringInvoiceTemplate $template): void
    {
        $template->update(['status' => RecurringStatus::Active]);
        Log::info("Resumed recurring template {$template->id}");
    }

    /**
     * Validate that companies have proper inter-company relationship.
     */
    protected function validateInterCompanyRelationship(int $companyId, int $targetCompanyId): void
    {
        if ($companyId === $targetCompanyId) {
            throw new \InvalidArgumentException('Source and target companies must be different');
        }

        // Check if partner relationship exists
        $partner = Partner::where('company_id', $targetCompanyId)
            ->where('linked_company_id', $companyId)
            ->first();

        if (!$partner) {
            throw new \InvalidArgumentException(
                "No partner relationship found between company {$companyId} and company {$targetCompanyId}"
            );
        }
    }

    /**
     * Calculate the initial next run date based on start date and frequency.
     */
    protected function calculateInitialNextRunDate(Carbon $startDate, $frequency, int $dayOfMonth): Carbon
    {
        $nextRun = $startDate->copy();

        // Adjust to the specified day of month
        if ($nextRun->day !== $dayOfMonth) {
            $nextRun->day($dayOfMonth);

            // If the adjusted date is in the past, move to next period
            if ($nextRun < $startDate) {
                $nextRun = match ($frequency) {
                    RecurringFrequency::Monthly => $nextRun->addMonth(),
                    RecurringFrequency::Quarterly => $nextRun->addMonths(3),
                    RecurringFrequency::Yearly => $nextRun->addYear(),
                };
            }
        }

        return $nextRun;
    }

    /**
     * Generate reference for recurring invoice.
     */
    protected function generateReference(RecurringInvoiceTemplate $template, Carbon $invoiceDate): string
    {
        return "{$template->reference_prefix}-{$template->id}-{$invoiceDate->format('Ym')}";
    }
}
