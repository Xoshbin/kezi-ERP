<?php

namespace AccounTech\FilamentAiHelper\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeepContextService
{
    /**
     * Build comprehensive context for a given model
     */
    public function buildDeepContext(Model $model): array
    {
        $context = [
            'basic_info' => $this->getBasicInfo($model),
            'relationships' => $this->getRelationshipContext($model),
            'historical_data' => $this->getHistoricalContext($model),
            'financial_metrics' => $this->getFinancialMetrics($model),
            'risk_indicators' => $this->getRiskIndicators($model),
            'business_insights' => $this->getBusinessInsights($model),
        ];

        return array_filter($context, fn($value) => !empty($value));
    }

    /**
     * Get basic model information
     */
    protected function getBasicInfo(Model $model): array
    {
        $info = [
            'model_type' => class_basename($model),
            'id' => $model->getKey(),
            'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
        ];

        // Add model-specific basic info
        if (method_exists($model, 'getDeepContextBasicInfo')) {
            $info = array_merge($info, $model->getDeepContextBasicInfo());
        }

        return $info;
    }

    /**
     * Get relationship context based on model type
     */
    protected function getRelationshipContext(Model $model): array
    {
        $modelClass = get_class($model);

        return match ($modelClass) {
            'App\Models\Invoice' => $this->getInvoiceRelationshipContext($model),
            'App\Models\VendorBill' => $this->getVendorBillRelationshipContext($model),
            'App\Models\Partner' => $this->getPartnerRelationshipContext($model),
            'App\Models\JournalEntry' => $this->getJournalEntryRelationshipContext($model),
            'App\Models\Payment' => $this->getPaymentRelationshipContext($model),
            default => [],
        };
    }

    /**
     * Get comprehensive invoice relationship context
     */
    protected function getInvoiceRelationshipContext($invoice): array
    {
        // Load only the relationships that exist and are needed
        $invoice->loadMissing([
            'customer',
            'currency',
            'invoiceLines',
            'payments'
        ]);

        $context = [];

        // Customer information and history
        if ($invoice->customer) {
            $customer = $invoice->customer;
            $context['customer'] = [
                'basic_info' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'type' => $customer->type?->value,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'tax_id' => $customer->tax_id,
                    'is_active' => $customer->is_active,
                ],
                'financial_summary' => [
                    'outstanding_balance' => $customer->getCustomerOutstandingBalance()?->getAmount(),
                    'overdue_balance' => $customer->getCustomerOverdueBalance()?->getAmount(),
                    'lifetime_value' => $customer->getTotalLifetimeValue()?->getAmount(),
                    'last_transaction_date' => $customer->getLastTransactionDate()?->format('Y-m-d'),
                    'has_overdue_amounts' => $customer->hasOverdueAmounts(),
                ],
                'payment_history' => $this->getCustomerPaymentHistory($customer),
                'transaction_summary' => $this->getCustomerTransactionSummary($customer),
                'risk_profile' => $this->getCustomerRiskProfile($customer),
            ];
        }

        // Invoice lines with basic information
        if ($invoice->invoiceLines->isNotEmpty()) {
            $context['invoice_lines'] = $invoice->invoiceLines->map(function ($line) {
                return [
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price?->getAmount(),
                    'currency' => $line->unit_price?->getCurrency()->getCurrencyCode(),
                    'total_amount' => $line->subtotal?->getAmount(),
                    'tax_amount' => $line->total_line_tax?->getAmount(),
                ];
            })->toArray();
        }

        // Payment information
        if ($invoice->payments->isNotEmpty()) {
            $context['payments'] = $invoice->payments->map(function ($payment) {
                return [
                    'payment_date' => $payment->payment_date?->format('Y-m-d'),
                    'amount' => $payment->amount?->getAmount(),
                    'currency' => $payment->amount?->getCurrency()->getCurrencyCode(),
                    'payment_method' => $payment->payment_method?->value,
                    'reference' => $payment->reference,
                ];
            })->toArray();
        }

        return $context;
    }

    /**
     * Get customer payment history and patterns
     */
    protected function getCustomerPaymentHistory($customer): array
    {
        $invoices = $customer->invoices()
            ->with(['payments', 'paymentDocumentLinks.payment'])
            ->whereIn('status', ['posted', 'paid'])
            ->orderBy('invoice_date', 'desc')
            ->limit(10)
            ->get();

        $paymentHistory = [];
        $totalInvoices = 0;
        $paidOnTime = 0;
        $averagePaymentDays = 0;
        $totalPaymentDays = 0;

        foreach ($invoices as $invoice) {
            $totalInvoices++;
            $paymentDate = null;
            $daysToPayment = null;

            if ($invoice->payments->isNotEmpty()) {
                $paymentDate = $invoice->payments->first()->payment_date;
                $daysToPayment = $invoice->due_date->diffInDays($paymentDate, false);
                $totalPaymentDays += abs($daysToPayment);

                if ($daysToPayment <= 0) {
                    $paidOnTime++;
                }
            }

            $paymentHistory[] = [
                'invoice_id' => $invoice->id,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'amount' => $invoice->total_amount?->getAmount(),
                'payment_date' => $paymentDate?->format('Y-m-d'),
                'days_to_payment' => $daysToPayment,
                'paid_on_time' => $daysToPayment !== null && $daysToPayment <= 0,
            ];
        }

        $averagePaymentDays = $totalInvoices > 0 ? round($totalPaymentDays / $totalInvoices, 1) : 0;
        $onTimePaymentRate = $totalInvoices > 0 ? round(($paidOnTime / $totalInvoices) * 100, 1) : 0;

        return [
            'recent_invoices' => $paymentHistory,
            'summary' => [
                'total_invoices_analyzed' => $totalInvoices,
                'on_time_payment_rate' => $onTimePaymentRate . '%',
                'average_payment_days' => $averagePaymentDays,
                'last_transaction_date' => $customer->getLastTransactionDate()?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get customer transaction summary
     */
    protected function getCustomerTransactionSummary($customer): array
    {
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        // Current year transactions
        $currentYearInvoices = $customer->invoices()
            ->whereYear('invoice_date', $currentYear)
            ->whereIn('status', ['posted', 'paid'])
            ->get();

        $lastYearInvoices = $customer->invoices()
            ->whereYear('invoice_date', $lastYear)
            ->whereIn('status', ['posted', 'paid'])
            ->get();

        return [
            'current_year' => [
                'year' => $currentYear,
                'invoice_count' => $currentYearInvoices->count(),
                'total_amount' => $currentYearInvoices->sum(fn($inv) => $inv->total_amount?->getAmount() ?? 0),
                'average_invoice_amount' => $currentYearInvoices->count() > 0
                    ? round($currentYearInvoices->avg(fn($inv) => $inv->total_amount?->getAmount() ?? 0), 2)
                    : 0,
            ],
            'last_year' => [
                'year' => $lastYear,
                'invoice_count' => $lastYearInvoices->count(),
                'total_amount' => $lastYearInvoices->sum(fn($inv) => $inv->total_amount?->getAmount() ?? 0),
                'average_invoice_amount' => $lastYearInvoices->count() > 0
                    ? round($lastYearInvoices->avg(fn($inv) => $inv->total_amount?->getAmount() ?? 0), 2)
                    : 0,
            ],
            'lifetime_value' => $customer->getTotalLifetimeValue()?->getAmount(),
        ];
    }

    /**
     * Get customer risk profile
     */
    protected function getCustomerRiskProfile($customer): array
    {
        $overdueInvoices = $customer->invoices()
            ->where('status', 'posted')
            ->where('due_date', '<', Carbon::now())
            ->get();

        $totalOverdue = $overdueInvoices->sum(fn($inv) => $inv->total_amount?->getAmount() ?? 0);
        $oldestOverdue = $overdueInvoices->min('due_date');

        return [
            'overdue_invoices_count' => $overdueInvoices->count(),
            'total_overdue_amount' => $totalOverdue,
            'oldest_overdue_date' => $oldestOverdue?->format('Y-m-d'),
            'days_oldest_overdue' => $oldestOverdue ? Carbon::now()->diffInDays($oldestOverdue) : 0,
            'credit_risk_level' => $this->calculateCreditRiskLevel($customer, $totalOverdue),
        ];
    }

    /**
     * Calculate credit risk level
     */
    protected function calculateCreditRiskLevel($customer, $totalOverdue): string
    {
        $paymentHistory = $this->getCustomerPaymentHistory($customer);
        $onTimeRate = (float) str_replace('%', '', $paymentHistory['summary']['on_time_payment_rate']);

        if ($totalOverdue > 0 && $onTimeRate < 70) {
            return 'HIGH';
        } elseif ($totalOverdue > 0 || $onTimeRate < 85) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Get historical context
     */
    protected function getHistoricalContext(Model $model): array
    {
        // This will be implemented based on model type
        return [];
    }

    /**
     * Get financial metrics
     */
    protected function getFinancialMetrics(Model $model): array
    {
        // This will be implemented based on model type
        return [];
    }

    /**
     * Get risk indicators
     */
    protected function getRiskIndicators(Model $model): array
    {
        // This will be implemented based on model type
        return [];
    }

    /**
     * Get business insights
     */
    protected function getBusinessInsights(Model $model): array
    {
        // This will be implemented based on model type
        return [];
    }

    // Placeholder methods for other model types
    protected function getVendorBillRelationshipContext($vendorBill): array { return []; }
    protected function getPartnerRelationshipContext($partner): array { return []; }
    protected function getJournalEntryRelationshipContext($journalEntry): array { return []; }
    protected function getPaymentRelationshipContext($payment): array { return []; }
}
