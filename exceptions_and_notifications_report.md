# Generic Exceptions and Danger Notifications Report

This report identifies files in the codebase that contain hardcoded exception messages (`throw new Exception('...')`) or danger notifications (`->danger()`) which could benefit from better error guidance, such as localized translations, persistent notifications, or direct configuration links.

## 1. Danger Notifications Lacking Persistence or Links
The following files contain `->danger()` notifications. If they indicate configuration errors or require user action, they should be persistent (`->persistent()`) and have actionable links.

### Accounting
- [x] `app/Filament/Pages/TaxReports.php`
- [x] `app/Filament/Actions/RegisterPaymentAction.php`
- [x] `app/Filament/Clusters/Accounting/Resources/Partners/RelationManagers/UnreconciledEntriesRelationManager.php`
- [x] `app/Filament/Clusters/Accounting/Resources/BankStatements/RelationManagers/BankStatementLinesRelationManager.php`
- [x] `app/Filament/Clusters/Accounting/Resources/AdjustmentDocuments/Pages/EditAdjustmentDocument.php`
- [x] `app/Filament/Clusters/Accounting/Resources/FiscalYears/Pages/EditFiscalYear.php`
- [x] `app/Filament/Clusters/Accounting/Resources/FiscalYears/FiscalYearResource.php`
- [x] `app/Filament/Clusters/Accounting/Resources/Invoices/InvoiceResource.php`
- [x] `app/Filament/Clusters/Accounting/Resources/FiscalYears/RelationManagers/PeriodsRelationManager.php`
- [x] `app/Filament/Clusters/Accounting/Resources/Payments/Pages/EditPayment.php`
- [x] `app/Filament/Clusters/Accounting/Resources/Invoices/Pages/EditInvoice.php`
- [x] `app/Filament/Clusters/Accounting/Resources/VendorBills/Pages/EditVendorBill.php`
- [x] `app/Livewire/Accounting/BankReconciliationMatcher.php`
- [x] `app/Filament/Clusters/Accounting/Resources/JournalEntries/Pages/EditJournalEntry.php`

### HR
- [x] `app/Services/HumanResources/LeaveManagementService.php`
- [x] `app/Services/HumanResources/EmployeeService.php`
- [x] `app/Services/HumanResources/AttendanceService.php`
- [x] `app/Services/HumanResources/PayrollService.php`
- [x] `app/Actions/HumanResources/*` (Various actions like SubmitCashAdvance, ProcessPayroll, etc.)
- [x] `app/Filament/Forms/Components/EmployeeSelectField.php`
- [x] `app/Filament/Clusters/HumanResources/Resources/Payrolls/Tables/PayrollsTable.php`

### Inventory
- [x] `app/Filament/Clusters/Inventory/Pages/*.php` (Valuation, Turnover, Reorder, Aging, LotTraceability Reports)
- [x] `app/Filament/Clusters/Inventory/Resources/StockMoves/*` (ConfirmStockMove, Create, Edit)
- [x] `app/Filament/Clusters/Inventory/Resources/StockPickingResource/*` (Validate, Confirm, Assign, Cancel)
- [x] `app/Filament/Clusters/Inventory/Resources/LandedCostResource/Pages/EditLandedCost.php`

### Purchase
- [x] `app/Filament/Clusters/Purchases/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`
- [x] `app/Filament/Clusters/Purchases/Resources/PurchaseOrders/Pages/EditPurchaseOrder.php`

### Sales
- [x] `app/Filament/Clusters/Sales/Resources/SalesOrders/Pages/EditSalesOrder.php`
- [x] `app/Filament/Clusters/Sales/Resources/SalesOrders/Pages/ViewSalesOrder.php`
- [x] `app/Filament/Clusters/Sales/Resources/Quotes/Pages/ViewQuote.php`

### Manufacturing
- [x] `app/Filament/Clusters/Manufacturing/Resources/ManufacturingOrderResource.php`
- [x] `app/Filament/Clusters/Manufacturing/Resources/ManufacturingOrderResource/Pages/ViewManufacturingOrder.php`

### Foundation
- [x] `app/Filament/Resources/NumberingSettingsResource/Pages/EditNumberingSettings.php`

### POS (New)
- [ ] `app/Filament/Clusters/Pos/Resources/PosReturns/Pages/ViewPosReturn.php`

---

## 2. Hardcoded Exception Messages
The following files include `throw new ...Exception(...)` statements using hardcoded strings. These must be converted to localized `__('...')` translations.

### Accounting (Partial Re-Scan Needed)
- [x] Services: `LockDateService.php`, `InterCompanyDocumentService.php`, `BalanceSheetService.php`, etc.
- [ ] Actions (Remaining): `BuildLoanPaymentJournalEntryAction.php`, `ComputeLoanScheduleAction.php`, `AccrueLoanInterestAction.php`, `CalculateEIRAction.php` (Loan currency missing)
- [ ] Actions (Remaining): `CreateJournalEntryForExpenseBillAction.php`, `CreateJournalEntryForPaymentAction.php`, `CreateJournalEntryForInvoiceAction.php`, `CreateJournalEntryForInventoryBillAction.php`, `CreateJournalEntryForDepreciationAction.php`, `CreateJournalEntryForPayrollAction.php`, `CreateJournalEntryForReconciliationAction.php`, `CreateJournalEntryAction.php`, `CreateJournalEntryForAssetAcquisitionAction.php`, `CreateJournalEntryForVendorBillAction.php`, `CreateJournalEntryForAdjustmentAction.php`
- [ ] Actions (Remaining): `PostDepreciationEntryAction.php` (Failed to refresh)

### HR (Partial Re-Scan Needed)
- [x] `app/Models/Position.php`
- [x] `app/Casts/SalaryCurrencyMoneyCast.php`
- [x] `app/Casts/PayrollCurrencyMoneyCast.php`
- [ ] Actions (Remaining): `CreateAttendanceAction.php` (Failed to refresh attendance)

### Inventory
- [x] Services: `GoodsReceiptService.php`, `StockMoveService.php`, etc.
- [x] Actions: `CreateAdjustmentDocumentAction.php`, `CreateGoodsReceiptFromPurchaseOrderAction.php`, etc.

### Purchase
- [x] Services: `PurchaseOrderService.php`, `VendorBillService.php`, etc.
- [x] Actions: `CreateVendorBillLineAction.php`, `CreateDebitNoteAction.php`, etc.

### Sales
- [x] Services: `InvoiceService.php`, `QuoteService.php`, etc.
- [x] Actions: `CreateCreditNoteAction.php`, `RejectQuoteAction.php`, etc.

### Manufacturing
- [x] Services: `BOMService.php`
- [x] Actions: `ConsumeComponentsAction.php`, `CreateJournalEntryForConsumptionAction.php`, `CreateJournalEntryForManufacturingAction.php`, `ConfirmManufacturingOrderAction.php`, `ScrapManufacturingAction.php`, `ProduceFinishedGoodsAction.php`, `StartProductionAction.php`, `CreateManufacturingOrderAction.php`

### Foundation
- [x] Services: `ExchangeRateService.php`, `CurrencyConverterService.php`
- [x] Casts: `MoneyCast.php`, `BaseCurrencyMoneyCast.php`, `OriginalCurrencyMoneyCast.php`, `DocumentCurrencyMoneyCast.php`
- [x] Observers: `CurrencyObserver.php`, `PartnerObserver.php`
- [x] Models: `Partner.php`

### Project Management
- [x] Services: `ProjectInvoicingService.php`
- [x] Actions: `SubmitTimesheetAction.php`, `RejectTimesheetAction.php`, `ApproveTimesheetAction.php`

### Payment (New)
- [ ] Actions: `CreateJournalEntryForLCChargeAction.php` (Default bank journal not configured)

---

### Status Summary

The audit has been expanded to include the `pos` and `payment` modules. Additionally, a deep scan of the `accounting` and `hr` modules revealed that while many items were already localized, specific complex actions were missed in previous passes.

| Module | Danger Notifications | Hardcoded Exceptions | `exceptions.php` | Status |
|--------|---|---|---|---|
| Accounting | ✅ 14 files | ⚠️ Partial Remainder | ✅ | Ongoing |
| HR | ✅ 7 files | ⚠️ Partial Remainder | ✅ | Ongoing |
| Inventory | ✅ 4 groups | ✅ | ✅ | Complete |
| Purchase | ✅ 2 files | ✅ | ✅ | Complete |
| Sales | ✅ 3 files | ✅ | ✅ | Complete |
| Manufacturing | ✅ 2 files | ✅ | ✅ | Complete |
| Foundation | ✅ 1 file | ✅ | ✅ | Complete |
| Project Management | ✅ none | ✅ | ✅ | Complete |
| POS | ❌ 1 missing | ✅ | ❌ | New Audit |
| Payment | ✅ none | ❌ 1 missing | ❌ | New Audit |
