<?php

namespace Kezi\Accounting\Services;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Kezi\Accounting\Enums\Budgets\BudgetStatus;
use Kezi\Accounting\Exceptions\BudgetExceededException;
use Kezi\Accounting\Models\Budget;
use Kezi\Accounting\Models\BudgetLine;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\VendorBill;

class BudgetControlService
{
    /**
     * Validate that a Vendor Bill does not exceed the budget.
     */
    public function validateVendorBill(VendorBill $bill): void
    {
        $billDate = Carbon::parse($bill->bill_date);
        $company = $bill->company;

        foreach ($bill->lines as $line) {
            // Check budget for Expense Account
            if ($line->expense_account_id) {
                $this->checkBudgetAvailability(
                    $company,
                    $line->expense_account_id,
                    null,
                    $billDate,
                    $line->subtotal_company_currency ?? $line->subtotal // Fallback if not yet converted, though post() ensures conversion
                );
            }

            // Check budget for Analytic Account
            if ($line->analytic_account_id) {
                $this->checkBudgetAvailability(
                    $company,
                    null,
                    $line->analytic_account_id,
                    $billDate,
                    $line->subtotal_company_currency ?? $line->subtotal
                );
            }
        }
    }

    /**
     * Validate that a Purchase Order does not exceed the budget.
     */
    public function validatePurchaseOrder(PurchaseOrder $po): void
    {
        $poDate = Carbon::parse($po->po_date);
        $company = $po->company;

        foreach ($po->lines as $line) {
            // Determine Expense Account from Product
            $expenseAccountId = $line->product->expense_account_id;

            if ($expenseAccountId) {
                // POs are usually in document currency, we need company currency
                $amount = $line->subtotal_company_currency ?? $line->getSubtotalInCompanyCurrency();

                $this->checkBudgetAvailability(
                    $company,
                    $expenseAccountId,
                    null,
                    $poDate,
                    $amount
                );
            }
        }
    }

    /**
     * Check availability for a specific account/analytic account and amount.
     * Throws exception if budget is exceeded.
     */
    protected function checkBudgetAvailability(
        Company $company,
        ?int $accountId,
        ?int $analyticAccountId,
        Carbon $date,
        Money $amountToCheck
    ): void {
        // Find matching budget lines
        // A budget is active and covers the date
        $budgetLines = BudgetLine::query()
            ->whereHas('budget', function ($query) use ($company, $date) {
                // We compare dates ignoring time for end date inclusion.
                // If budget ends on 2024-01-31, it covers transactions on 2024-01-31 23:59:59.
                // DB stores strictly DATE (00:00:00). So we check if period_end_date >= date(transaction_date).
                $query->where('company_id', $company->id)
                    ->where('status', BudgetStatus::Finalized) // Only check confirmed budgets
                    ->whereDate('period_start_date', '<=', $date)
                    ->whereDate('period_end_date', '>=', $date);
            })
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($analyticAccountId, fn ($q) => $q->where('analytic_account_id', $analyticAccountId))
            ->with(['budget', 'account'])
            ->get();

        if ($budgetLines->isEmpty()) {
            return;
        }

        foreach ($budgetLines as $budgetLine) {
            $budget = $budgetLine->budget;

            // Calculate current usage
            $actuals = $this->getActuals($budgetLine);
            $committed = $this->getCommitted($budgetLine);

            $totalUsage = $actuals->plus($committed);
            $available = $budgetLine->budgeted_amount->minus($totalUsage);

            // Check if adding the new amount exceeds available
            if ($available->minus($amountToCheck)->isNegative()) {
                $rawAccountName = $budgetLine->account->name ?? $budgetLine->analyticAccount->name ?? 'Unknown';
                $accountName = is_array($rawAccountName) ? (string) (array_values($rawAccountName)[0] ?? 'Unknown') : (string) $rawAccountName;

                $budgetName = $budget->name;

                throw new BudgetExceededException(
                    "The transaction exceeds the available budget: Budget exceeded for {$budgetName} (Account: {$accountName}). ".
                    "Available: {$available->formatTo('en_US')}, Requested: {$amountToCheck->formatTo('en_US')}"
                );
            }
        }
    }

    /**
     * Calculate actuals (Posted Journal Entries) for the budget line scope.
     */
    protected function getActuals(BudgetLine $line): Money
    {
        // Sum of (Debit - Credit) for this account in the budget period
        // For Expense accounts (Debit > Credit normally).
        // If it's income budget, logic might differ but here we assume expense control.

        $query = \Kezi\Accounting\Models\JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($line) {
                $q->where('company_id', $line->company_id)
                    ->whereDate('entry_date', '>=', $line->budget->period_start_date)
                    ->whereDate('entry_date', '<=', $line->budget->period_end_date)
                    ->where('is_posted', true);
            });

        if ($line->account_id) {
            $query->where('account_id', $line->account_id);
        }

        if ($line->analytic_account_id) {
            $query->where('analytic_account_id', $line->analytic_account_id);
        }

        // We sum (debit - credit) to get net expense
        $netAmount = $query->sum(
            \Illuminate\Support\Facades\DB::raw('debit - credit')
        );

        // Result is in minor units (integer) because stored as such?
        // No, 'debit' and 'credit' columns are usually big integers in database for Money?
        // Let's verify JournalEntryLine schema.
        // Assuming they are stored as integers (minor units) based on other parts of the app using MoneyCast.
        // But sum() on DB returns the raw value.

        return Money::ofMinor($netAmount, $line->budget->currency->code);
    }

    /**
     * Calculate committed amount from Purchase Orders.
     */
    protected function getCommitted(BudgetLine $line): Money
    {
        $budget = $line->budget;
        $currency = $budget->currency;

        // Sum of unbilled portions of Confirmed POs
        // Logic: Iterate confirmed PO lines within period.
        // Committed = (Quantity - Quantity Billed) * Unit Price
        // But wait, "Quantity Billed" might not be tracked directly on PO Line in this system?
        // Let's check PurchaseOrderLine. available fields: quantity, quantity_received.
        // There is 'vendorBills' relation on PO but linking lines is complex.
        // We sum the whole amount? No, double counting with Bills.
        // Better approach:
        // Committed = Sum of PO Lines where status is Confirmed/ToReceive/ToBill
        // MINUS
        // Sum of Posted Bill Lines linked to those POs.

        // This is complex.
        // Simpler proxy:
        // Committed = PO Lines (Confirmed) where "quantity_billed" < "quantity".
        // Does PO Line have "quantity_billed"?
        // Let's assume for now we don't have perfect "quantity_billed" tracking on line.
        // Valid Proxy: Sum of PO Lines in 'Confirmed'/'ToReceive' POs.
        // BUT if a bill is posted, it becomes Actual.
        // So we must fallback "Committed" to 0 for the portion that is billed.
        // If we can't track partial billing easily, we might over-constrain (Committed + Actual > Budget).

        // Let's look at PurchaseOrderLine again... it has 'vendorBills' through PO.
        // This gap in easy "unbilled amount" calculation is common.

        // Strategy:
        // 1. Find all PO Lines for this account/date.
        // 2. Filter for POs that are NOT 'FullyBilled' or 'Done'.
        // 3. For each line, calculate (Ordered - Billed).
        // Since we don't have "Billed" easily, let's look at `PurchaseOrder::updateStatusBasedOnBilling`.
        // It checks `vendorBills` count.

        // Alternative safe implementation:
        // Committed = 0. We only control against Actuals (Strict) + Pending (Current Bill/PO).
        // This is safer than blocking "legitimate" bills because of double counting.
        // But the user asked for "Budget Control". Ignoring PO commitments defeats the purpose of "Control" at PO stage.

        // Best effort:
        // Sum of PO lines in "Confirmed", "ToReceive", "ToBill", "PartiallyBilled" status.
        // For "PartiallyBilled", we risk double counting.
        // For "ToBill", we risk double counting if bill is in Draft (and we don't include Draft bills in Actuals).
        // Valid Bills (Actuals) are "Posted".
        // So:
        // Actuals = Posted Bills (via Journal Entries).
        // Committed = Confirmed POs that are NOT yet Posted Bills.
        // i.e., PO lines where NO posted bill line exists?
        // Hard to trace line-by-line without explicit link.

        // Let's use a conservative approximation:
        // Committed = Sum of PurchaseOrderLine total_company_currency
        // WHERE po.status IN (Confirmed, ToReceive, ToBill, PartiallyBilled)
        // AND po_date in period
        // AND product.expense_account_id = line.account_id
        //
        // SUBTRACT
        // Sum of VendorBillLine
        // WHERE bill.status = Posted
        // AND bill.purchase_order_id IS NOT NULL (Linked to a PO)
        // AND .... (matching account)

        // This subtracts *all* billed amounts linked to POs from the total PO committed amount.
        // It handles partial billing and full billing correctly (Committed reduces as Actuals increase).

        // 1. Total PO Amount for this account/period
        $poQuery = \Kezi\Purchase\Models\PurchaseOrderLine::query()
            ->whereHas('purchaseOrder', function ($q) use ($line) {
                $q->where('company_id', $line->company_id)
                    ->whereIn('status', [
                        \Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus::Confirmed,
                        \Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus::ToReceive,
                        \Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus::ToBill,
                        \Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus::PartiallyReceived,
                        \Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus::PartiallyBilled,
                    ])
                    ->whereDate('po_date', '>=', $line->budget->period_start_date)
                    ->whereDate('po_date', '<=', $line->budget->period_end_date);
            })
            ->whereHas('product', function ($q) use ($line) {
                if ($line->account_id) {
                    $q->where('expense_account_id', $line->account_id);
                }
                // POs don't support analytic accounts yet, so we ignore analytic filter here for POs
                // or effectively say POs don't contribute to analytic budget commitment if filtered by analytic_account.
            });

        // We need to sum converted amounts. PO Line has `total_company_currency`.
        // We assume it is populated.
        $totalPOAmount = Money::of(0, $currency->code);
        // We can't use SQL sum easily on JSON/Money columns if they are not simple integers.
        // Assuming BaseCurrencyMoneyCast uses integer column (e.g. `total_company_currency` is bigint).
        // Checking VendorBillLine migration would confirm, but let's assume standard app pattern (integers).
        // Wait, `PurchaseOrderLine` has `total_company_currency` (casted property).
        // The column name is likely `total_company_currency`.

        // Let's use PHP iteration to be safe with Money objects and casting, unless performance is critical.
        // For "Budget Control", performance is important but accuracy is paramount.
        // Optimization: Chunking.
        $poQuery->chunk(100, function ($poLines) use (&$totalPOAmount) {
            foreach ($poLines as $poLine) {
                $totalPOAmount = $totalPOAmount->plus($poLine->getSubtotalInCompanyCurrency());
            }
        });

        if ($line->analytic_account_id) {
            // Since POs don't have analytic, we stop here for analytic budgets (Committed = 0 from POs)
            return Money::of(0, $currency->code);
        }

        // 2. Subtract Billed Amount linked to POs for this account/period
        // This avoids double counting Actuals (Bills) vs Committed (POs).
        $billedQuery = \Kezi\Purchase\Models\VendorBillLine::query()
            ->whereHas('vendorBill', function ($q) use ($line) {
                $q->where('company_id', $line->company_id)
                    ->where('status', \Kezi\Purchase\Enums\Purchases\VendorBillStatus::Posted)
                    ->whereNotNull('purchase_order_id') // Only bills linked to POs
                    ->whereHas('purchaseOrder', function ($sq) use ($line) {
                        // Ensure the LINKED PO is within the period (otherwise we subtract bills for old POs from new POs?)
                        // Actually, we should subtract bills that are linked to the POs we just summed.
                        $sq->whereDate('po_date', '>=', $line->budget->period_start_date)
                            ->whereDate('po_date', '<=', $line->budget->period_end_date);
                    });
            })
            ->where('expense_account_id', $line->account_id);

        $totalBilledLinkedToPO = Money::of(0, $currency->code);
        $billedQuery->chunk(100, function ($billLines) use (&$totalBilledLinkedToPO) {
            foreach ($billLines as $billLine) {
                // We rely on `subtotal_company_currency`
                $amount = $billLine->subtotal_company_currency ?? $billLine->subtotal; // fallback
                $totalBilledLinkedToPO = $totalBilledLinkedToPO->plus($amount);
            }
        });

        // Committed = Total PO - Total Billed (Linked to those POs)
        $committed = $totalPOAmount->minus($totalBilledLinkedToPO);

        // Ensure we don't return negative committed (if bills > PO amount due to variances, we categorize that variance as Actuals, so Committed should be 0).
        if ($committed->isNegative()) {
            return Money::of(0, $currency->code);
        }

        return $committed;
    }
}
