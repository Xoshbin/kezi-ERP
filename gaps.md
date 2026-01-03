
I'll conduct a comprehensive gap analysis of your accounting ERP system. Let me start by gathering information about the current codebase structure and implementations.

Now let me gather more detailed information about the modules structure, enums, reports, and existing tests to understand the complete picture:

Let me continue gathering more information about HR/Payroll, Inventory management, fiscal year closing, and other key ERP components:

Let me gather more information about additional ERP features like multi-currency, taxes, audit logging, user permissions, and analytics:

Let me gather final pieces of information on taxes, policies, Livewire components, and any existing gap documentation:

Based on my comprehensive analysis of your codebase, I can now provide a detailed gap analysis of your Laravel-based headless accounting ERP system.

---

# Comprehensive Gap Analysis: JMeryar ERP System

## Executive Summary

Your system demonstrates strong foundational architecture for accounting with excellent double-entry bookkeeping, immutability controls, and multi-currency support. However, there are significant gaps in ERP workflow completeness, reporting depth, and advanced accounting features. This analysis is organized by functional area with priority ratings.

---

## 1. Core Accounting Functionality

### 1.1 Double-Entry Bookkeeping ✅ Mostly Complete

**Implemented:**
- Strong double-entry enforcement through `JournalEntry` and `JournalEntryLine` models
- Debit/credit balance validation before posting
- Source document linking (invoices, bills, payments → journal entries)
- Cryptographic hash chain for tamper detection
- Immutability of posted entries via `JournalEntryObserver`
- Reversal entries through `ReverseJournalEntryAction`

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No journal entry approval workflow** | Medium | No multi-level approval for manual journal entries; entries go from draft → posted directly without review gates |
| **Missing recurring journal entries** | Medium | No automation for monthly accruals, prepaid expense amortization, or other recurring entries |
| **No intercompany journal entries** | Low | Parent-child company structure exists but no automated intercompany transactions/eliminations |

### 1.2 Financial Statement Generation ⚠️ Partially Complete

**Implemented Reports:**
- Balance Sheet (`BalanceSheetService`)
- Profit & Loss Statement (`ProfitAndLossStatementService`)
- Trial Balance (`TrialBalanceService`)
- General Ledger (`GeneralLedgerService`)
- Partner Ledger (`PartnerLedgerService`)
- Aged Receivables & Payables
- Tax Report (`TaxReportService`)

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| ~~**No Cash Flow Statement**~~ | ~~Critical~~ | ✅ **COMPLETED** - `CashFlowStatementService` implemented using indirect method |
| **No Statement of Changes in Equity** | High | Required for complete financial statements per accounting standards |
| **No comparative period reports** | High | Cannot compare current period vs prior period side-by-side |
| **No consolidated financial statements** | Medium | Multi-company exists but no consolidation/elimination logic |
| **No report export to Excel/PDF** | Medium | Reports display in UI but no standardized export functionality visible |
| **No customizable report periods** | Low | Fiscal year start assumed as Jan 1st (hardcoded in `BalanceSheetService`) |

### 1.3 Period-End Closing Procedures ✅ Well Implemented

**Implemented:**
- Fiscal Year model with states (Draft, Open, Closing, Closed)
- Fiscal Period model for monthly/quarterly periods
- `CloseFiscalYearAction` with Anglo-Saxon closing entries
- P&L accounts zeroed to Retained Earnings
- `CloseFiscalPeriodAction` and `ReopenFiscalPeriodAction`
- Lock date enforcement via `LockDateService`
- Opening balance entries via `CreateOpeningBalanceEntryAction`

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No automated period-end checklist** | Medium | No system-enforced steps before closing (e.g., verify all invoices posted, reconcile bank accounts) |
| **No draft journal entry check UI** | Low | Service validates but no dashboard widget showing blocking issues |
| **No automatic fiscal period creation** | Low | Periods must be manually created for each fiscal year |

### 1.4 Multi-Currency Accounting ✅ Complete

**Implemented:**
- `Currency` and `CurrencyRate` models
- `CurrencyConverterService` for conversions
- Exchange rate tracking at document creation
- `ExchangeGainLossService` for realized gains/losses on payments
- Document currency vs company base currency handling
- `DocumentCurrencyMoneyCast` and `BaseCurrencyMoneyCast`
- **Unrealized Exchange Gain/Loss Revaluation** (`CurrencyRevaluationService`, `PerformCurrencyRevaluationAction`) - Period-end revaluation of foreign currency AR/AP balances with automatic journal entry creation
- **Automatic Exchange Rate Updates** (`FetchExchangeRatesCommand`, `ExchangeRateService`) - Scheduled rate fetching from multiple providers including exchangerate-api.com and Central Bank of Iraq
- **Multi-Provider Exchange Rate Support** (`ExchangeRateApiProvider`, `CentralBankOfIraqProvider`) - Configurable provider selection with fallback support
- **Exchange Rate Validation & Approval Workflow** (`ExchangeRateValidationService`, `ExchangeRateChange` model) - Significant rate change detection with approval workflow
- **Currency Gain/Loss Report** (`CurrencyGainLossReportService`) - Comprehensive FX reporting with realized vs unrealized breakdown and period-over-period analysis

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No currency translation for consolidation** | Medium | Multi-company exists but no translation methods (current rate, temporal) |

### 1.5 Tax Calculation and Reporting ⚠️ Partially Complete

**Implemented:**
- Tax model with types (Sales, Purchase, Both)
- Tax rate configuration per company
- Fiscal Position model with tax/account mappings
- Tax amounts calculated on invoice/bill lines
- Tax Report for period-based tax summary
- is_recoverable flag for input tax deduction

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No Iraqi-specific tax forms** | High | No VAT return form, withholding tax reports, or Iraqi tax authority formats |
| **No tax groups/composite taxes** | Medium | Cannot combine multiple taxes (e.g., federal + state) on single line |
| **No reverse charge VAT** | Medium | Common for B2B cross-border transactions |
| **No tax exemption certificates** | Low | For tax-exempt customers/products |
| **No withholding tax on payments** | Low | Common requirement for service payments |

### 1.6 Asset Management and Depreciation ✅ Well Implemented

**Implemented:**
- Asset model with lifecycle (Draft → Confirmed → Depreciating → FullyDepreciated → Sold)
- Depreciation methods enum (currently Straight-Line implemented)
- Depreciation schedule computation (`ComputeDepreciationScheduleAction`)
- Depreciation entry posting with journal entries
- Asset disposal with gain/loss calculation (`DisposeAssetAction`)
- Asset, Accumulated Depreciation, and Expense account linking

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **Only Straight-Line depreciation implemented** | High | Declining balance, units of production methods exist in enum but not coded |
| **No asset revaluation** | Medium | Cannot adjust asset values upward per IFRS |
| **No asset impairment** | Medium | No workflow for recording impairment losses |
| **No asset split/merge** | Low | Cannot split one asset into multiple or combine assets |
| **No automatic asset creation from purchases** | Low | No link from vendor bill to asset creation |

### 1.7 Cost Accounting and Inventory Valuation ⚠️ Significant Gaps

**Implemented (per inventory-gap.md analysis):**
- Valuation methods enum: FIFO, LIFO, AVCO, Standard
- `InventoryValuationService` for outgoing stock (COGS)
- `InventoryCostLayer` model for FIFO/LIFO layers
- Stock move valuation with journal entries for outgoing

**Critical Gaps (documented in inventory-gap.md):**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| ~~**Incoming valuation bypasses InventoryValuationService**~~ | ~~Critical~~ | ✅ **RESOLVED** - `CreateStockMovesOnVendorBillConfirmed` now correctly calls `createConsolidatedIncomingStockJournalEntry()` which creates FIFO/LIFO cost layers via `processIncomingStockWithoutJournalEntry()`. Fixed namespace bug in `calculateIncomingCostPerUnitEnhanced` (`Modules\Inventory\Models\VendorBill` → `Modules\Purchase\Models\VendorBill`). Tests: `VendorBillFIFOCostLayerTest.php`, `VendorBillFIFOCostLayerFilamentTest.php`. |
| **Standard costing not implemented** | High | Enum value exists but logic branches missing; will behave incorrectly |
| ~~**No StockQuant for per-location inventory**~~ | ~~Critical~~ | ✅ **RESOLVED** - `StockQuant` is now the source of truth for per-location inventory; `Product.quantity_on_hand` is a computed accessor aggregating from `StockQuant` |
| **Wrong sales destination location** | High | Sales moves go to Vendor location instead of Customer location (bug) |
| **Adjustments don't create stock moves** | Medium | No location traceability for inventory adjustments |

### 1.8 Bank Reconciliation ⚠️ Partially Complete

**Implemented:**
- `BankStatement` and `BankStatementLine` models
- `BankReconciliationService` for matching
- `CreateJournalEntryForReconciliationAction`
- Reconciliation status tracking on payments
- Company-level reconciliation enable/disable flag
- Multi-item reconciliation via `reconcileMultiple()`

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No bank statement import (CSV/OFX/MT940)** | High | Manual entry only; no file parsing |
| **No auto-matching rules** | High | No configurable rules to auto-match based on amount/reference |
| **No bank feed integration** | Medium | No API connections to banks (acceptable for Iraqi market) |
| **No outstanding check/deposit reports** | Medium | No report showing unreconciled items by age |
| **No void/bounce check handling** | Low | No workflow for returned checks |

---

## 2. ERP System Components

### 2.1 Sales Order Management ⚠️ Workflow Incomplete

**Implemented:**
- `SalesOrder` model with comprehensive status workflow (Quotation → Confirmed → Delivered → Invoiced → Done)
- `Quote` model for pre-sales quotations
- Sales order lines with quantity tracking (ordered/delivered/invoiced)
- `CreateInvoiceFromSalesOrderAction`
- Status enum with proper state machine logic

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No delivery order (picking) generation from SO** | Critical | Stock moves created directly from invoices, not from sales orders |
| **No partial delivery tracking** | High | `quantity_delivered` field exists but no partial fulfillment workflow |
| **No backorder management** | High | Cannot create backorders for unfulfilled quantities |
| **No delivery schedule** | Medium | No expected delivery date per line with multi-date support |
| **No sales order confirmation workflow** | Medium | No customer acceptance/PO reference integration |
| **No shipping/carrier integration** | Low | Acceptable for current scope |

### 2.2 Purchase Order Management ⚠️ Workflow Incomplete

**Implemented:**
- `PurchaseOrder` model with comprehensive status workflow
- `RequestForQuotation` model for RFQ process
- `CreateVendorBillFromPurchaseOrderAction`
- Purchase order lines with quantity tracking
- `CreatePurchaseOrderAction` and related DTOs

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No goods receipt (GRN) process** | Critical | VendorBill creates stock moves; no separate receiving workflow |
| **No partial receiving** | High | Cannot receive partial quantities and track backorders |
| **No three-way matching** | High | No enforcement of PO-GRN-Invoice matching before payment |
| **No purchase requisition workflow** | Medium | No internal request → approval → PO process |
| **No vendor price lists** | Medium | No vendor-specific pricing with quantity breaks |
| **No dropship support** | Low | No vendor-direct-to-customer shipping |

### 2.3 Inventory Management ⚠️ Significant Gaps

**Implemented:**
- `StockMove`, `StockMoveLine`, `StockMoveProductLine` models
- `StockLocation` with types (Internal, Customer, Vendor, InventoryAdjustment)
- `Lot` model with expiration date and FEFO scope methods
- `InventoryCostLayer` for cost tracking
- `StockPicking` model (exists but underutilized)
- `StockQuantService` with FEFO allocation logic
- `StockReservationService` (exists)
- Multiple inventory reports (Valuation, Aging, Turnover, Traceability, Reorder Status)

**Gaps (from inventory-gap.md + additional analysis):**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| ~~**StockQuant not fully integrated**~~ | ~~Critical~~ | ✅ **RESOLVED** - `StockQuant` is now the source of truth; `Product.quantity_on_hand` is a computed accessor; `VendorBillObserver`, `InventoryValuationService`, and `InventoryMovementValidationService` all use `StockQuantService` |
| **Stock moves go directly to Done** | Critical | No draft/confirmed/assigned/done workflow; no reservations enforce availability |
| **No serial number tracking** | High | Only lot tracking; individual unit serialization not supported |
| **Reordering rules incomplete** | High | Model exists but no scheduler/job to auto-generate purchase orders |
| **No min/max inventory levels enforcement** | High | ReorderingRule exists but not integrated into operations |
| **No warehouse/zone structure** | Medium | Locations exist but no warehouse entity grouping them |
| **No cycle counting/physical inventory** | Medium | No periodic count workflow with variance handling |
| **No MTO (Make-to-Order) routing** | Low | All products treated as MTS (Make-to-Stock) |

### 2.4 Manufacturing/Production Planning ❌ Not Implemented

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No Bill of Materials (BOM)** | Low* | No product recipes/components for manufacturing |
| **No work orders/production orders** | Low* | No manufacturing workflow |
| **No work centers/routing** | Low* | No production capacity planning |
| **No WIP (Work-in-Progress) accounting** | Low* | No manufacturing cost accumulation |

*Priority is Low because manufacturing may be out of scope for a trading/service-focused Iraqi market ERP. Increase to High if manufacturing is needed.

### 2.5 Human Resources and Payroll ✅ Well Implemented

**Implemented:**
- `Employee` model with comprehensive personal/employment data
- `EmploymentContract` model with salary, allowances, leave entitlements
- `Department` and `Position` models for org structure
- `PayrollService` with full payroll processing
- Payroll journal entry creation (`CreateJournalEntryForPayrollAction`)
- Payment creation from payroll (`CreatePaymentFromPayrollAction`)
- `Attendance` model with overtime calculation
- `LeaveRequest` and `LeaveType` models
- Deduction calculations (income tax, social security, health insurance, pension)

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No Iraqi payroll tax rules** | High | Generic tax calculations; no Iraqi income tax brackets or social security rules |
| **No leave balance tracking** | High | LeaveRequest exists but no automated balance deduction/accrual |
| **No timesheet module** | Medium | Attendance exists but no project-based time tracking |
| **No employee self-service** | Medium | No portal for employees to view payslips, request leave |
| **No loan/advance management** | Medium | No employee loan tracking with payroll deductions |
| **No end-of-service benefits calculation** | Medium | Important for Iraqi labor law compliance |
| **No attendance integration** | Low | No biometric/access control integration |

### 2.6 Project Accounting and Cost Centers ⚠️ Partially Complete

**Implemented:**
- `AnalyticAccount` model for cost centers/projects
- `AnalyticPlan` for grouping analytic accounts
- `JournalEntryLine.analytic_account_id` for tagging transactions
- `BudgetLine` links to analytic accounts

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No project profitability report** | High | No revenue vs cost comparison by analytic account |
| **No analytic distribution rules** | Medium | Cannot auto-allocate costs (e.g., 60% Dept A, 40% Dept B) |
| **No project billing/WIP** | Medium | No project-based invoicing or unbilled revenue tracking |
| **No cost allocation/reallocation** | Medium | No period-end overhead allocation journals |
| **No timesheet-to-project costing** | Low | No employee cost rate × hours = project labor cost |

### 2.7 Budgeting and Forecasting ⚠️ Basic Implementation

**Implemented:**
- `Budget` model with periods and types (Analytic, Financial)
- `BudgetLine` with budgeted, achieved, committed amounts
- Budget status (Draft, Finalized)
- Link to Account and AnalyticAccount

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No budget vs actual report** | Critical | BudgetLine stores amounts but no report compares to GL actuals |
| **No automatic achieved amount update** | High | `achieved_amount` field exists but not auto-calculated from journal entries |
| **No committed amount tracking** | High | `committed_amount` exists but no link to approved POs/SOs |
| **No budget versioning** | Medium | Cannot maintain multiple versions (original, revised, forecast) |
| **No budget approval workflow** | Medium | Direct draft → finalized with no approval gates |
| **No rolling forecasts** | Low | No auto-extend budgets based on trends |
| **No budget variance alerts** | Low | No notifications when spending exceeds budget |

### 2.8 Reporting and Analytics ⚠️ Needs Enhancement

**Implemented:**
- Standard financial reports (as listed in 1.2)
- Dashboard widgets (Financial KPIs, Income vs Expense Chart, Cash Flow Widget)
- Inventory reports (Valuation, Aging, Turnover, Traceability, Reorder Status)
- Report pages with date filtering

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No custom report builder** | Medium | Users cannot create ad-hoc reports |
| **No drill-down from reports to transactions** | Medium | Cannot click a balance to see underlying entries |
| **No saved report filters** | Low | Cannot save frequently used filter combinations |
| **No report scheduling/email** | Low | Cannot schedule reports to email stakeholders |
| **No graphical analytics dashboards** | Low | Basic charts exist; no advanced BI visualizations |

---

## 3. System Architecture & Compliance

### 3.1 Audit Trail ✅ Excellent Implementation

**Implemented:**
- `AuditLog` model with comprehensive tracking
- `AuditLogObserver` attached to all key models via `#[ObservedBy]` attribute
- Records user_id, company_id, event_type, old_values, new_values, IP address
- Polymorphic auditable relationship for any model
- Cryptographic hash chain on `JournalEntry` for tamper detection
- Dedicated `AuditLogResource` in Filament

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No audit log archiving** | Low | Large tables over time; no purge/archive policy |
| **No login/logout audit** | Low | Model changes tracked but not authentication events |
| **No field-level sensitivity** | Low | All changes logged equally; no redaction of sensitive fields in logs |

### 3.2 Data Integrity Controls ✅ Well Implemented

**Implemented:**
- `Brick\Money\Money` objects throughout for financial precision
- Immutability enforcement via observers
- Lock date validation preventing backdated entries
- Foreign key constraints in migrations
- Soft deletes on key entities (Partner, Product)
- Deletion prevention for entities with financial records
- Hash chain verification for journal entries
- Sequence service for atomic document numbering

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No data validation rules report** | Low | No UI showing which constraints protect data |
| **No data integrity check command** | Low | No artisan command to verify hash chains |

### 3.3 User Access Controls and Authorization ⚠️ Needs Work

**Implemented:**
- Multi-company/tenant architecture via Filament
- User-Company many-to-many relationship
- Laravel Policies for key models (Invoice, VendorBill, JournalEntry, Payroll, Quote)
- `canAccessTenant()` in User model
- Status-based restrictions (e.g., cannot delete posted entries)

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **Policies return `true` for most actions** | Critical | Most policies have `// TODO` comments and allow all actions |
| **No role-based access control (RBAC)** | Critical | No Role model; no admin/accountant/clerk differentiation |
| **No permission system** | Critical | No granular permissions (view invoices, create invoices, post invoices) |
| **No separation of duties enforcement** | High | Same user can create, approve, and post entries |
| **No field-level access control** | Medium | Cannot hide sensitive fields from certain users |
| **No approval workflows by role** | Medium | No multi-level approval based on amount thresholds |
| **No password policy enforcement** | Low | No complexity, expiry, or history requirements |
| **No two-factor authentication** | Low | No 2FA for sensitive operations |

### 3.4 Integration Points Between Modules ⚠️ Gaps Exist

**Implemented:**
- Sales → Accounting: Invoice posting creates journal entries
- Purchase → Accounting: VendorBill posting creates journal entries
- Payment → Accounting: Payment confirmation creates journal entries
- HR → Accounting: Payroll creates journal entries and payments
- Inventory → Accounting: Stock moves create valuation journal entries
- Sales/Purchase → Inventory: Document posting creates stock moves

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **Sales Order → Inventory disconnect** | Critical | Delivery/picking not driven from SO; goes directly from invoice |
| **Purchase Order → Inventory disconnect** | Critical | No receiving independent of vendor bill |
| **No API/webhook integration layer** | Medium | Internal events exist but no external API |
| **Budget → Operations disconnect** | Medium | Committed amounts not auto-updated from PO/SO |
| **Project → Billing disconnect** | Low | No project invoicing integration |

### 3.5 Compliance with Accounting Standards ⚠️ Needs Enhancement

**Implemented:**
- Anglo-Saxon accounting method (COGS on sale, not on receipt)
- Double-entry bookkeeping
- Accrual basis accounting
- Lock dates for period control
- Reversing entries instead of modifications

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No IFRS-specific features** | High | No lease accounting (IFRS 16), no revenue recognition (IFRS 15) |
| **No Iraqi accounting standards** | High | No specific Iraqi GAAP compliance features |
| **No statutory chart of accounts template** | Medium | No pre-built Iraqi COA |
| **No legal document numbering compliance** | Medium | Sequences exist but no Iraqi invoice numbering requirements |

### 3.6 Performance and Scalability ⚠️ Potential Issues

**Implemented:**
- Eager loading on key relationships (`$with` on models)
- Database indexing on foreign keys (via Laravel migrations)
- Queue support for heavy operations
- SQLite for development; likely MySQL/PostgreSQL for production

**Gaps:**

| Gap | Priority | Explanation |
|-----|----------|-------------|
| **No database optimization analysis** | Medium | No evidence of query optimization for large datasets |
| **Aggregate calculations may be slow** | Medium | Reports calculate on-the-fly from line items; no summary tables |
| **No caching layer for reports** | Low | Reports regenerate fully on each request |
| **No horizontal scaling consideration** | Low | Single-server architecture assumed |

---

## 4. Priority Summary and Implementation Roadmap

### Critical Priority (Must Address First)

1. ~~**StockQuant integration**~~ ✅ **COMPLETED** - Inventory tracking now uses per-location quantities via `StockQuant`
2. **Incoming inventory valuation** - Unify with `InventoryValuationService`
3. ~~**Cash Flow Statement**~~ ✅ **COMPLETED** - Implemented using indirect method with Operating/Investing/Financing sections
4. **RBAC/Permissions system** - Implement proper access controls
5. **Budget vs Actual report** - Core budgeting functionality
6. **Sales Order → Delivery workflow** - Proper order fulfillment
7. **Purchase Order → Receiving workflow** - Proper procurement cycle

### High Priority (Address in Next Sprint)

1. **Unrealized exchange gain/loss revaluation** - Period-end accuracy
2. **Statement of Changes in Equity** - Financial statement completeness
3. **Three-way matching** (PO-GRN-Invoice) - Procurement control
4. **Partial delivery/receiving** - Backorder management
5. **Bank statement import** - Reduce manual data entry
6. **Iraqi payroll tax rules** - Compliance
7. **Declining balance depreciation** - Asset management completeness
8. **Leave balance tracking** - HR module completeness

### Medium Priority (Plan for Future Releases)

1. Recurring journal entries
2. Multi-level approval workflows
3. Project profitability reporting
4. Auto-matching rules for bank reconciliation
5. Budget versioning and approval
6. Serial number tracking
7. Comparative period reports
8. Analytic distribution rules
9. Cycle counting
10. Employee self-service portal

### Low Priority (Nice to Have)

1. Intercompany eliminations
2. Manufacturing module
3. Report scheduling
4. Two-factor authentication
5. Custom report builder
6. Audit log archiving

---

## 5. Iraqi Market-Specific Recommendations

Given the documentation mentions this is targeted at the Iraqi market:

| Requirement | Current State | Recommendation |
|-------------|---------------|----------------|
| **Iraqi Dinar (IQD) as base currency** | Supported via multi-currency | Ensure IQD-specific decimal handling (0 decimals) |
| **Arabic RTL support** | Translations exist (ar, ckb) | Verify Filament RTL rendering |
| **Iraqi tax regulations** | Generic tax model | Add specific tax rates and forms |
| **Cash-based economy** | Cash payment method exists | Emphasize cash tracking; de-emphasize bank integrations |
| **Manual processes** | By design | Good alignment with design philosophy |
| **Government reporting** | Not implemented | Research Iraqi statutory requirements |

---

This comprehensive gap analysis should help prioritize development efforts. The architecture is solid, and the core accounting engine is well-designed. The primary gaps are in ERP workflow completeness (especially the order-to-delivery and procure-to-pay cycles) and in security/authorization controls.
