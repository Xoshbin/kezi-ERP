# Generic Exceptions and Danger Notifications Report

This report identifies files in the codebase that contain hardcoded exception messages (`throw new Exception('...')`) or danger notifications (`->danger()`) which could benefit from better error guidance, such as localized translations, persistent notifications, or direct configuration links (similar to commit `e57a5f57`).

## 1. Danger Notifications Lacking Persistence or Links
The following files contain `->danger()` notifications. These notifications often auto-dismiss quickly. If they indicate configuration errors or require user action, consider making them persistent (`->persistent()`) and adding actionable links within the message.

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
- `app/Filament/Clusters/Sales/Resources/SalesOrders/Pages/EditSalesOrder.php`
- `app/Filament/Clusters/Sales/Resources/SalesOrders/Pages/ViewSalesOrder.php`

### Manufacturing
- `app/Filament/Clusters/Manufacturing/Resources/ManufacturingOrderResource.php`
- `app/Filament/Clusters/Manufacturing/Resources/ManufacturingOrderResource/Pages/ViewManufacturingOrder.php`

### Foundation
- `app/Filament/Resources/NumberingSettingsResource/Pages/EditNumberingSettings.php`


---

## 2. Hardcoded Exception Messages
The following files include `throw new ...Exception(...)` statements. These exceptions often use plain, hardcoded English strings instead of localized `__('...')` translations. Consider enhancing them to provide translatable text, contextual data, or direct configuration links.

*(Note: Test directories, seeders, and vendor directories have been excluded where possible, but some services or actions may still be listed.)*

### Accounting
- [x] Services: `LockDateService.php`, `InterCompanyDocumentService.php`, `BalanceSheetService.php`, `CurrencyTranslationService.php`, `PartnerLedgerService.php`, `TaxReportService.php`, `BudgetControlService.php`, `JournalEntryService.php`, `BankReconciliationService.php`, `AssetService.php`, `AccountService.php`, `ExchangeGainLossService.php`
- [x] Actions: `CreateJournalEntryForLoanInitialRecognitionAction.php`, `ReclassifyLoanCurrentPortionAction.php`, `PerformCurrencyRevaluationAction.php`, `DisposeAssetAction.php`, and other Loan-related actions.
- [x] Observers: `DepreciationEntryObserver.php`, `AssetObserver.php`, `JournalObserver.php`, `LockDateObserver.php`
- [x] Other: `NotInLockedPeriod.php` (Rule), `RegisterPaymentAction.php`

### HR
- [x] `app/Models/Position.php`
- [x] `app/Casts/SalaryCurrencyMoneyCast.php`
- [x] `app/Casts/PayrollCurrencyMoneyCast.php`

### Inventory
- [x] Services: `GoodsReceiptService.php`, `StockMoveService.php`, `InventoryValuationService.php`, `StockQuantService.php`, `InventoryReportingService.php`, `TransferOrderService.php`
- [x] Actions: `CreateAdjustmentDocumentAction.php`, `CreateGoodsReceiptFromPurchaseOrderAction.php`, `ValidateGoodsReceiptAction.php`, `ProcessIncomingStockAction.php`, `UpdateProductInventoryStatsAction.php`, `CreateJournalEntryForStockMoveAction.php`, `ReceiveTransferAction.php`, `PostLandedCostAction.php`, `ScrapAction.php`, `ShipTransferAction.php`, `CreateInventoryAdjustmentAction.php`
- [x] Listeners: `CreateStockMovesOnVendorBillConfirmed.php`
- [x] Resources: `CreateStockMove.php`, `CreateProduct.php`

### Purchase
- [x] Services: `PurchaseOrderService.php`, `VendorBillService.php`, `ThreeWayMatchingService.php`
- [x] Actions: `CreateVendorBillLineAction.php`, `CreateDebitNoteAction.php`, `UpdateVendorBillAction.php`, `ConvertRFQToPurchaseOrderAction.php`, `UpdatePurchaseOrderAction.php`, `CreateVendorBillAction.php`
- [x] Observers: `VendorBillObserver.php`
- [x] Resources: `EditPurchaseOrder.php`

### Sales
- Services: `InvoiceService.php`
- Actions: `CreateCreditNoteAction.php`, `RejectQuoteAction.php`, `CreateStockMovesForInvoiceAction.php`, `UpdateQuoteAction.php`, `ConvertQuoteToSalesOrderAction.php`, `SendQuoteAction.php`, `ConvertQuoteToInvoiceAction.php`, `UpdateInvoiceAction.php`, `CreateInvoiceAction.php`, `UpdateSalesOrderAction.php`, `CancelQuoteAction.php`, `AcceptQuoteAction.php`, `CreateQuoteRevisionAction.php`, `CreateInvoiceLineAction.php`
- Observers: `QuoteObserver.php`, `QuoteLineObserver.php`
- Resources: `EditSalesOrder.php`

### Manufacturing
- Services: `BOMService.php`
- Actions: `ConsumeComponentsAction.php`, `CreateJournalEntryForConsumptionAction.php`, `CreateJournalEntryForManufacturingAction.php`, `ConfirmManufacturingOrderAction.php`, `ScrapManufacturingAction.php`, `ProduceFinishedGoodsAction.php`, `StartProductionAction.php`, `CreateManufacturingOrderAction.php`

### Foundation
- Services: `ExchangeRateService.php`, `CurrencyConverterService.php`
- Casts: `MoneyCast.php`, `BaseCurrencyMoneyCast.php`, `OriginalCurrencyMoneyCast.php`, `DocumentCurrencyMoneyCast.php`
- Observers: `CurrencyObserver.php`, `PartnerObserver.php`
- Models: `Partner.php`

### Project Management
- Services: `ProjectInvoicingService.php`
- Actions: `SubmitTimesheetAction.php`, `RejectTimesheetAction.php`, `ApproveTimesheetAction.php`

---

### Recommended Next Steps:
1. Review the `->danger()` notifications in the **Accounting** and **Inventory** resources/pages first. Add `->persistent()` and consider if an action array (`->actions([ Action::make('fix')->url(...) ])`) would help user flow.
2. Review `Exception` messages in core **Services** and **Actions** (e.g., `LockDateService`, `InventoryValuationService`). Convert hardcoded strings like `throw new Exception("Inventory is locked")` to `throw new Exception(__('inventory::messages.locked_error', ['link' => ...]))`.
