<?php

namespace App\Console\Commands;

use App\Actions\Sales\CreateRecurringInterCompanyInvoiceAction;
use App\Models\User;
use App\Services\RecurringInterCompanyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRecurringInterCompanyCharges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:process-inter-company-charges 
                            {--dry-run : Show what would be processed without actually creating invoices}
                            {--template-id= : Process only a specific template ID}
                            {--force : Force processing even if not due}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring inter-company charges and generate invoices/vendor bills';

    public function __construct(
        protected RecurringInterCompanyService $recurringService,
        protected CreateRecurringInterCompanyInvoiceAction $createInvoiceAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting recurring inter-company charges processing...');

        $dryRun = $this->option('dry-run');
        $templateId = $this->option('template-id');
        $force = $this->option('force');

        try {
            // Get due templates
            $templates = $this->getDueTemplates($templateId, $force);

            if ($templates->isEmpty()) {
                $this->info('No templates due for processing.');
                return self::SUCCESS;
            }

            $this->info("Found {$templates->count()} template(s) due for processing.");

            $processed = 0;
            $errors = 0;

            // Get system user for automated processing
            $systemUser = $this->getSystemUser();

            foreach ($templates as $template) {
                $this->line("Processing template: {$template->name} (ID: {$template->id})");

                if ($dryRun) {
                    $this->info("  [DRY RUN] Would generate invoice for {$template->targetCompany->name}");
                    $processed++;
                    continue;
                }

                try {
                    $this->processTemplate($template, $systemUser);
                    $processed++;
                    $this->info("  ✓ Successfully processed template {$template->id}");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ✗ Failed to process template {$template->id}: {$e->getMessage()}");
                    Log::error("Failed to process recurring template {$template->id}", [
                        'template_id' => $template->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Summary
            $this->newLine();
            $this->info("Processing complete:");
            $this->info("  - Processed: {$processed}");
            if ($errors > 0) {
                $this->error("  - Errors: {$errors}");
            }

            return $errors > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Fatal error during processing: {$e->getMessage()}");
            Log::error('Fatal error in recurring charges processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Get templates that are due for processing.
     */
    protected function getDueTemplates($templateId, $force)
    {
        if ($templateId) {
            $template = \App\Models\RecurringInvoiceTemplate::find($templateId);
            if (!$template) {
                $this->error("Template with ID {$templateId} not found.");
                return collect();
            }

            if (!$force && !$template->isDue()) {
                $this->warn("Template {$templateId} is not due for processing. Use --force to override.");
                return collect();
            }

            return collect([$template]);
        }

        return $this->recurringService->getDueTemplates();
    }

    /**
     * Process a single template.
     */
    protected function processTemplate($template, $systemUser): void
    {
        DB::transaction(function () use ($template, $systemUser) {
            // Generate the recurring invoice DTO
            $recurringInvoiceDTO = $this->recurringService->generateFromTemplate($template, $systemUser);

            // Create the invoice and vendor bill
            $result = $this->createInvoiceAction->execute($recurringInvoiceDTO, $systemUser);

            // Update the template
            $this->recurringService->updateTemplateAfterGeneration($template);

            Log::info("Successfully processed recurring template {$template->id}", [
                'template_id' => $template->id,
                'invoice_id' => $result->invoice->id,
                'vendor_bill_id' => $result->vendorBill->id,
                'reference' => $result->reference,
            ]);
        });
    }

    /**
     * Get or create a system user for automated processing.
     */
    protected function getSystemUser(): User
    {
        // Try to find a system user or use the first admin user
        $systemUser = User::where('email', 'system@company.com')->first();
        
        if (!$systemUser) {
            // Fallback to first user (in a real system, you'd want a dedicated system user)
            $systemUser = User::first();
            
            if (!$systemUser) {
                throw new \RuntimeException('No users found in the system. Cannot process recurring charges.');
            }
            
            $this->warn("Using user {$systemUser->email} for automated processing. Consider creating a dedicated system user.");
        }

        return $systemUser;
    }
}
