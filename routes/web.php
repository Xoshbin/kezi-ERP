<?php

use App\Actions\Sales\GenerateInvoicePdfAction;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

// PDF Generation Routes (Protected by authentication)
Route::middleware(['auth'])->group(function () {
    // Invoice PDF routes
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice, GenerateInvoicePdfAction $action) {
        // Check if user can view this invoice - user must have access to the company
        $user = Auth::user();
        if (! $user->companies()->where('companies.id', $invoice->company_id)->exists()) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $template = request('template', $invoice->company->pdf_template ?? 'classic');

        return $action->execute($invoice, $template);
    })->name('invoices.pdf');

    Route::get('/invoices/{invoice}/pdf/download', function (Invoice $invoice, GenerateInvoicePdfAction $action) {
        // Check if user can view this invoice - user must have access to the company
        $user = Auth::user();
        if (! $user->companies()->where('companies.id', $invoice->company_id)->exists()) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $template = request('template', $invoice->company->pdf_template ?? 'classic');

        return $action->download($invoice, $template);
    })->name('invoices.pdf.download');

    // PDF Preview route for settings
    Route::get('/pdf/preview/{company}', function (Company $company) {
        // Check if user can access this company - user must have access to the company
        $user = Auth::user();
        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            abort(403, 'Unauthorized access to company settings.');
        }

        // Find a sample invoice for preview
        $invoice = $company->invoices()->where('status', '!=', InvoiceStatus::Draft)->first();

        if (! $invoice) {
            return response()->json(['error' => 'No posted invoices found for preview'], 404);
        }

        $template = request('template', $company->pdf_template ?? 'classic');

        return app(GenerateInvoicePdfAction::class)->execute($invoice, $template);
    })->name('pdf.preview');

    // Docs: Payments guide
    Route::get('/docs/payments', function () {
        $path = base_path('docs/payments.md');
        if (! file_exists($path)) {
            abort(404);
        }
        $markdown = file_get_contents($path);
        $html = Str::markdown($markdown);

        return view('docs.payments', ['html' => $html]);
    })->name('docs.payments');
});
