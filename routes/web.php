<?php

use App\Actions\Sales\GenerateInvoicePdfAction;
use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

// PDF Generation Routes (Protected by authentication)
Route::middleware(['auth'])->group(function () {
    // Invoice PDF routes
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice, GenerateInvoicePdfAction $action) {
        // Check if user can view this invoice
        if ($invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $template = request('template', $invoice->company->pdf_template ?? 'classic');
        return $action->execute($invoice, $template);
    })->name('invoices.pdf');

    Route::get('/invoices/{invoice}/pdf/download', function (Invoice $invoice, GenerateInvoicePdfAction $action) {
        // Check if user can view this invoice
        if ($invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $template = request('template', $invoice->company->pdf_template ?? 'classic');
        return $action->download($invoice, $template);
    })->name('invoices.pdf.download');

    // PDF Preview route for settings
    Route::get('/pdf/preview/{company}', function (Company $company) {
        // Check if user can access this company
        if ($company->id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to company settings.');
        }

        // Find a sample invoice for preview
        $invoice = $company->invoices()->where('status', '!=', Invoice::STATUS_DRAFT)->first();

        if (!$invoice) {
            return response()->json(['error' => 'No posted invoices found for preview'], 404);
        }

        $template = request('template', $company->pdf_template ?? 'classic');
        return app(GenerateInvoicePdfAction::class)->execute($invoice, $template);
    })->name('pdf.preview');
});
