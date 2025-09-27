<?php

namespace Modules\Sales\Actions\Sales;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class GenerateInvoicePdfAction
{
    public function execute(\Modules\Sales\Models\Invoice $invoice, string $template = 'classic'): Response
    {
        // Note: We now allow printing draft invoices for quotes/offers/previews

        // Eager load all necessary relationships in a single query for performance
        $invoice->load([
            'company',
            'customer',
            'invoiceLines.product',
            'invoiceLines.tax',
            'invoiceLines.incomeAccount',
            'currency',
        ]);

        // Validate template exists
        $templatePath = "pdfs.invoice.{$template}";
        if (! View::exists($templatePath)) {
            $templatePath = 'pdfs.invoice.classic'; // Fallback to classic template
        }

        // Configure PDF with proper settings for multi-language support
        $pdf = Pdf::loadView($templatePath, [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'currency' => $invoice->currency,
        ]);

        // Configure PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
        ]);

        $fileName = "invoice-{$invoice->invoice_number}.pdf";

        // Return as an inline browser response
        // Use ->download($fileName) to force a download instead
        return $pdf->stream($fileName);
    }

    public function download(\Modules\Sales\Models\Invoice $invoice, string $template = 'classic'): Response
    {
        // Note: We now allow downloading draft invoices for quotes/offers/previews

        $invoice->load([
            'company',
            'customer',
            'invoiceLines.product',
            'invoiceLines.tax',
            'invoiceLines.incomeAccount',
            'currency',
        ]);

        $templatePath = "pdfs.invoice.{$template}";
        if (! View::exists($templatePath)) {
            $templatePath = 'pdfs.invoice.classic';
        }

        $pdf = Pdf::loadView($templatePath, [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'currency' => $invoice->currency,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
        ]);

        $fileName = "invoice-{$invoice->invoice_number}.pdf";

        // Force download
        return $pdf->download($fileName);
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableTemplates(): array
    {
        return [
            'classic' => 'Classic Template',
            'modern' => 'Modern Template',
            'minimal' => 'Minimal Template',
        ];
    }
}
