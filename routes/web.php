<?php

use App\Actions\Sales\GenerateInvoicePdfAction;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

// Locale switching route
Route::post('/locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'setLocale'])
    ->name('locale.set')
    ->where('locale', 'en|ckb|ar');

// Documentation Routes (Public access)
Route::prefix('docs')->group(function () {
    Route::get('/', [\App\Http\Controllers\Docs\DocumentController::class, 'index'])->name('docs.index');

    // JSON search index (must be before slug route)
    Route::get('/index.json', function () {
        $items = \App\Services\DocumentationService::make()->buildIndex();
        return response()->json($items)->header('Content-Type', 'application/json');
    })->name('docs.index.json');

    // Payments doc placeholder used by tests and UI links
    Route::get('/payments', function () {
        return response('Payments docs placeholder', 200);
    })->name('docs.payments');

    // Must be last: catch-all slug route (allows slashes for nested docs)
    Route::get('/{slug}', [\App\Http\Controllers\Docs\DocumentController::class, 'show'])
        ->where('slug', '.*')
        ->name('docs.show');
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

});
