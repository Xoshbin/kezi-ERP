-----

# Comprehensive Accounting Test Scenario (Revised)

## A PhD Accountant's Step-by-Step Testing Guide

### Prerequisites (Already Available via Seeders)

Before starting this scenario, ensure the following are created using seeders:

  - **Company**: "Jmeryar Solutions" with IQD as base currency
  - **Currencies**: USD ($1.00 = 1460 IQD) and IQD
  - **Chart of Accounts**: Complete account structure from AccountSeeder
  - **Journals**: Sales, Purchases, Bank (USD/IQD), Cash (USD/IQD), Miscellaneous, Depreciation
  - **Partners**: Paykar Tech Supplies (Vendor), Hawre Trading Group (Customer), Hiwa Computer Center (Vendor), Zryan Tech Store (Customer)
  - **Products**: Consulting Services, Development Services, Wireless Router, CAT6 Cable, Network Switch
  - **Taxes**: VAT 10% and Tax Exempt
  - **Users**: Admin user for operations

-----

## Phase 1: Initial Capital Investment & Setup

### Step 1: Owner's Initial Capital Investment

**Business Context**: The owner invests $50,000 USD cash to start the business.

**Action**: Create manual journal entry

  - **Journal**: Miscellaneous Operations
  - **Date**: January 1, 2024
  - **Reference**: "CAPITAL-001"
  - **Description**: "Initial capital investment by owner"
  - **Lines**:
      - Debit: Cash (USD) - $50,000
      - Credit: Owner's Equity - $50,000

**Expected Outcomes**:

**Journal Entry Created**:

```
Date: January 1, 2024 | Reference: CAPITAL-001
Dr. Cash (USD) [110201]                    $50,000.00
    Cr. Owner's Equity [320101]                        $50,000.00
```

**Account Balances After Posting**:

  - Cash (USD) [110201]: $50,000.00 (Debit Balance)
  - Owner's Equity [320101]: $50,000.00 (Credit Balance)

**Balance Sheet Impact**:

  - Total Current Assets: $50,000.00
  - Total Assets: $50,000.00
  - Total Equity: $50,000.00
  - Accounting Equation: Assets ($50,000) = Liabilities ($0) + Equity ($50,000) ✓

**System Controls**:

  - Journal entry assigned sequential number (e.g., JE-001)
  - Cryptographic hash generated for immutability
  - Audit log created with user, timestamp, IP address
  - Entry cannot be modified or deleted once posted

### Step 2: Open Bank Account with Initial Deposit

**Business Context**: Transfer $40,000 from cash to open a USD bank account.

**Action**: Create manual journal entry

  - **Journal**: Bank (USD)
  - **Date**: January 2, 2024
  - **Reference**: "BANK-OPEN-001"
  - **Description**: "Initial bank deposit"
  - **Lines**:
      - Debit: Bank Account (USD) - $40,000
      - Credit: Cash (USD) - $40,000

**Expected Outcomes**:

**Journal Entry Created**:

```
Date: January 2, 2024 | Reference: BANK-OPEN-001
Dr. Bank Account (USD) [110101]            $40,000.00
    Cr. Cash (USD) [110201]                            $40,000.00
```

**Account Balances After Posting**:

  - Bank Account (USD) [110101]: $40,000.00 (Debit Balance)
  - Cash (USD) [110201]: $10,000.00 (Debit Balance - reduced from $50,000)

**Balance Sheet Impact**:

  - Current Assets composition changed but total unchanged: $50,000.00
  - Cash: $10,000.00
  - Bank: $40,000.00
  - No impact on liabilities or equity

**Cash Flow Classification**:

  - Operating Activity: Transfer between cash accounts (internal movement)
  - No net change in total cash and cash equivalents

-----

## Phase 2: Multi-Currency Operations

### Step 3: Currency Exchange Transaction

**Business Context**: Exchange $10,000 USD to IQD at rate 1:1460 for local operations.

**Action**: Create manual journal entry

  - **Journal**: Miscellaneous Operations
  - **Date**: January 5, 2024
  - **Reference**: "FX-001"
  - **Description**: "Currency exchange USD to IQD"
  - **Lines**:
      - Debit: Cash (IQD) - 14,600,000 IQD
      - Credit: Cash (USD) - $10,000

**Expected Outcomes**:

**Journal Entry Created**:

```
Date: January 5, 2024 | Reference: FX-001 | Exchange Rate: 1 USD = 1,460 IQD
Dr. Cash (IQD) [110202]                    14,600,000 IQD
    Cr. Cash (USD) [110201]                            $10,000.00
```

**Account Balances After Posting**:

  - Cash (USD) [110201]: $0.00 (fully exchanged)
  - Cash (IQD) [110202]: 14,600,000 IQD (Debit Balance)
  - Bank Account (USD) [110101]: $40,000.00 (unchanged)

**Multi-Currency Accounting Impact**:

  - Base currency (IQD) equivalent: 14,600,000 IQD
  - Foreign currency tracking: Exchange rate 1:1460 recorded
  - Journal entry lines show both original currency and base currency amounts
  - Currency gain/loss: None (direct exchange at market rate)

**Balance Sheet Impact (in Base Currency IQD)**:

  - Total Cash and Bank: 73,000,000 IQD ($40,000 × 1460 + 14,600,000)
  - No change in total assets value when converted to base currency

### Step 3.5: Bank-to-Bank Transfer (New)

**Business Context**: Transfer $1,000 from the USD bank account to the IQD bank account for operational needs.

**Action**: Create internal bank transfer

  - **Journal**: Bank (USD)
  - **Date**: January 6, 2024
  - **Reference**: "INT-BANK-001"
  - **Description**: "Internal transfer from USD to IQD bank account"
  - **Lines**:
      - **Entry 1 (Send):**
          - Debit: Liquidity Transfers - $1,000
          - Credit: Bank Account (USD) - $1,000
      - **Entry 2 (Receive):**
          - Debit: Bank Account (IQD) - 1,460,000 IQD
          - Credit: Liquidity Transfers - 1,460,000 IQD

**Expected Outcomes**:

**Journal Entries Created**:

```
Date: January 6, 2024 | Reference: INT-BANK-001-SEND
Dr. Liquidity Transfers [109101]            $1,000.00
    Cr. Bank Account (USD) [110101]                    $1,000.00

Date: January 6, 2024 | Reference: INT-BANK-001-RECV
Dr. Bank Account (IQD) [110102]            1,460,000 IQD
    Cr. Liquidity Transfers [109101]                   1,460,000 IQD
```

**Account Balances After Posting**:

  - Bank Account (USD) [110101]: $39,000.00 (Debit Balance - reduced from $40,000)
  - Bank Account (IQD) [110102]: 1,460,000 IQD (Debit Balance)
  - Liquidity Transfers [109101]: $0.00 (cleared)

**Balance Sheet Impact**:

  - Reclassification of assets. Total cash and cash equivalents remain unchanged.

-----

## Phase 3: Asset Acquisition & Management

### Step 4: Purchase Office Equipment (Fixed Asset)

**Business Context**: Buy office furniture for $5,000 USD from Paykar Tech Supplies.

**Action**: Create vendor bill

  - **Vendor**: Paykar Tech Supplies
  - **Currency**: USD
  - **Bill Date**: January 10, 2024
  - **Due Date**: February 10, 2024
  - **Lines**:
      - Product: Office Equipment (create as asset)
      - Quantity: 1
      - Unit Price: $5,000
      - Tax: Tax Exempt
      - Asset Details:
          - Useful Life: 5 years
          - Salvage Value: $500
          - Depreciation Method: Straight-line
          - Asset Account: Office Equipment (150101)
          - Accumulated Depreciation Account: Acc. Depreciation - Office Equipment (150199)
          - Depreciation Expense Account: Depreciation Expense (530301)

**Expected Outcomes**:

**Vendor Bill Creation (Draft Status)**:

  - Bill Number: Not assigned (assigned only upon posting)
  - Status: Draft
  - Total Amount: $5,000.00
  - Due Date: February 10, 2024

**Upon Posting - Journal Entry Created**:

```
Date: January 10, 2024 | Reference: BILL-001
Dr. Office Equipment [150101]              $5,000.00
    Cr. Accounts Payable [210101]                     $5,000.00
```

**Account Balances After Posting**:

  - Office Equipment [150101]: $5,000.00 (Debit Balance)
  - Accounts Payable [210101]: $5,000.00 (Credit Balance)

**Asset Management Impact**:

  - **Asset Record Created**:
      - Asset ID: Generated automatically
      - Purchase Value: $5,000.00
      - Salvage Value: $500.00
      - Useful Life: 5 years (60 months)
      - Depreciation Method: Straight-line
      - Monthly Depreciation: ($5,000 - $500) ÷ 60 = $75.00/month

**Depreciation Schedule Generated**:

  - 60 monthly entries created in "Draft" status
  - First depreciation date: January 31, 2024
  - Last depreciation date: December 31, 2028
  - Total depreciable amount: $4,500.00

**Balance Sheet Impact**:

  - Fixed Assets increase by $5,000.00
  - Current Liabilities increase by $5,000.00
  - No impact on equity (asset acquisition with liability)

**Vendor Management**:

  - Paykar Tech Supplies payable balance: $5,000.00
  - Payment terms: Net 30 days
  - Due date tracking activated

### Step 5: Pay for Office Equipment

**Business Context**: Pay the vendor bill using bank transfer.

**Action**: Create payment

  - **Payment Type**: Outbound
  - **Journal**: Bank (USD)
  - **Amount**: $5,000
  - **Date**: January 15, 2024
  - **Link to**: Vendor Bill from Step 4

**Expected Outcomes**:

**Payment Creation and Confirmation**:

  - Payment Number: PAY-001 (assigned upon confirmation)
  - Payment Type: Outbound
  - Status: Confirmed
  - Amount: $5,000.00

**Journal Entry Created**:

```
Date: January 15, 2024 | Reference: PAY-001
Dr. Accounts Payable [210101]              $5,000.00
    Cr. Bank Account (USD) [110101]                   $5,000.00
```

**Account Balances After Posting**:

  - Bank Account (USD) [110101]: $34,000.00 (reduced from $39,000 after internal transfer)
  - Accounts Payable [210101]: $0.00 (reduced from $5,000)

**Vendor Bill Status Update**:

  - Status changed from "Posted" to "Paid"
  - Payment allocation: 100% of bill amount
  - Payment date recorded: January 15, 2024
  - Outstanding balance: $0.00

**Cash Flow Impact**:

  - Operating Cash Flow: $0.00 (no operating impact - asset purchase)
  - Investing Cash Flow: -$5,000.00 (capital expenditure for fixed asset)

**Vendor Management**:

  - Paykar Tech Supplies balance: $0.00
  - Payment history updated
  - Vendor aging report: No outstanding amounts

### Step 6: Process Monthly Depreciation

**Business Context**: Record first month's depreciation for office equipment.

**Action**: Process depreciation entry

  - **Asset**: Office Equipment
  - **Depreciation Date**: January 31, 2024
  - **Amount**: $75

**Expected Outcomes**:

**Depreciation Entry Processing**:

  - Depreciation Entry Status: Changed from "Draft" to "Posted"
  - Amount: $75.00
  - Depreciation Date: January 31, 2024

**Journal Entry Created Automatically**:

```
Date: January 31, 2024 | Reference: DEPR/Office Equipment/2024-01
Dr. Depreciation Expense [530301]          $75.00
    Cr. Acc. Depreciation - Office Equipment [150199]  $75.00
```

**Account Balances After Posting**:

  - Depreciation Expense [530301]: $75.00 (Debit Balance)
  - Acc. Depreciation - Office Equipment [150199]: $75.00 (Credit Balance)
  - Office Equipment [150101]: $5,000.00 (unchanged - original cost)

**Asset Valuation Impact**:

  - **Gross Asset Value**: $5,000.00 (Office Equipment account)
  - **Accumulated Depreciation**: $75.00
  - **Net Book Value**: $5,000.00 - $75.00 = $4,925.00
  - **Remaining Depreciable Amount**: $4,425.00 ($4,500 - $75)

**Balance Sheet Presentation**:

```
Fixed Assets:
  Office Equipment                    $5,000.00
  Less: Accumulated Depreciation        (75.00)
  Net Office Equipment                $4,925.00
```

**Income Statement Impact**:

  - Operating Expenses increase by $75.00
  - Net Income decreases by $75.00
  - Depreciation properly matched to asset usage period

**Depreciation Schedule Update**:

  - January 2024 entry: Posted
  - February 2024 entry: Next due for processing
  - Remaining entries: 59 months in "Draft" status

-----

## Phase 4: Inventory Management & Sales

### Step 7: Purchase Inventory for Resale

**Business Context**: Buy 10 wireless routers at $800 each from Hiwa Computer Center.

**Action**: Create vendor bill

  - **Vendor**: Hiwa Computer Center
  - **Currency**: USD
  - **Bill Date**: February 1, 2024
  - **Lines**:
      - Product: Wireless Router (storable)
      - Quantity: 10
      - Unit Price: $800
      - Tax: VAT 10%
      - Total Line: $8,000
      - Tax Amount: $800
      - Total Bill: $8,800

**Expected Outcomes**:

**Vendor Bill Creation and Posting**:

  - Bill Number: BILL-002 (assigned upon posting)
  - Vendor: Hiwa Computer Center
  - Total Amount: $8,800.00

**Journal Entry Created**:

```
Date: February 1, 2024 | Reference: BILL-002
Dr. Inventory [130101]                     $8,000.00
Dr. VAT Receivable [120102]                  $800.00
    Cr. Accounts Payable [210101]                     $8,800.00
```

**Account Balances After Posting**:

  - Inventory [130101]: $8,000.00 (Debit Balance)
  - VAT Receivable [120102]: $800.00 (Debit Balance)
  - Accounts Payable [210101]: $8,800.00 (Credit Balance)

**Inventory Management Impact**:

  - **Stock Move Created**:
      - Product: Wireless Router
      - Movement Type: Incoming (Vendor → Inventory)
      - Quantity: +10 units
      - Unit Cost: $800.00
      - Total Value: $8,000.00
      - Location: Main Warehouse (default)

**Product Costing**:

  - **Cost Basis Established**: $800.00 per unit
  - **Inventory Valuation Method**: FIFO (First In, First Out)
  - **Total Inventory Quantity**: 10 units
  - **Total Inventory Value**: $8,000.00
  - **Average Cost**: $800.00 per unit

**VAT Accounting**:

  - **Input VAT**: $800.00 (recoverable from tax authority)
  - **VAT Rate Applied**: 10%
  - **Net Purchase Amount**: $8,000.00
  - **Gross Purchase Amount**: $8,800.00

**Balance Sheet Impact**:

  - Current Assets increase by $8,800.00 (Inventory + VAT Receivable)
  - Current Liabilities increase by $8,800.00 (Accounts Payable)
  - No net impact on equity

**Vendor Management**:

  - Hiwa Computer Center payable balance: $8,800.00
  - Due date: March 3, 2024 (30 days)
  - Purchase history updated

### Step 8: Sell Inventory to Customer

**Business Context**: Sell 3 wireless routers to Hawre Trading Group at $1,200 each.

**Action**: Create customer invoice

  - **Customer**: Hawre Trading Group
  - **Currency**: USD
  - **Invoice Date**: February 5, 2024
  - **Due Date**: March 5, 2024
  - **Lines**:
      - Product: Wireless Router
      - Quantity: 3
      - Unit Price: $1,200
      - Tax: VAT 10%
      - Subtotal: $3,600
      - Tax Amount: $360
      - Total: $3,960

**Expected Outcomes**:

**Customer Invoice Creation and Posting**:

  - Invoice Number: INV-001 (assigned upon posting)
  - Customer: Hawre Trading Group
  - Total Amount: $3,960.00

**Primary Journal Entry (Sales)**:

```
Date: February 5, 2024 | Reference: INV-001
Dr. Accounts Receivable [120101]           $3,960.00
    Cr. Product Sales [410101]                        $3,600.00
    Cr. VAT Payable [220101]                            $360.00
```

**Secondary Journal Entry (Cost of Goods Sold)**:

```
Date: February 5, 2024 | Reference: INV-001-COGS
Dr. Cost of Goods Sold [510101]            $2,400.00
    Cr. Inventory [130101]                             $2,400.00
```

**Account Balances After Posting**:

  - Accounts Receivable [120101]: $3,960.00 (Debit Balance)
  - Product Sales [410101]: $3,600.00 (Credit Balance)
  - VAT Payable [220101]: $360.00 (Credit Balance)
  - Cost of Goods Sold [510101]: $2,400.00 (Debit Balance)
  - Inventory [130101]: $5,600.00 (reduced from $8,000)

**Inventory Management Impact**:

  - **Stock Move Created (Outgoing)**:
      - Product: Wireless Router
      - Movement Type: Outgoing (Inventory → Customer)
      - Quantity: -3 units
      - Unit Cost: $800.00 (FIFO basis)
      - Total Cost: $2,400.00

**Inventory Position After Sale**:

  - **Remaining Quantity**: 7 units (10 - 3)
  - **Remaining Value**: $5,600.00 (7 × $800)
  - **Average Cost**: $800.00 per unit (unchanged)

**Revenue Recognition**:

  - **Gross Sales**: $3,600.00
  - **Cost of Goods Sold**: $2,400.00
  - **Gross Profit**: $1,200.00
  - **Gross Margin**: 33.33% ($1,200 ÷ $3,600)

**VAT Accounting**:

  - **Output VAT**: $360.00 (payable to tax authority)
  - **VAT Rate Applied**: 10%
  - **Net VAT Position**: $360.00 payable - $800.00 receivable = $440.00 net receivable

**Customer Management**:

  - Hawre Trading Group receivable balance: $3,960.00
  - Due date: March 5, 2024 (30 days)
  - Credit terms: Net 30
  - Sales history updated

-----

## Phase 5: Service Revenue & Multi-Currency

### Step 9: Provide Consulting Services (IQD)

**Business Context**: Provide consulting services to Zryan Tech Store for 2,190,000 IQD.

**Action**: Create customer invoice

  - **Customer**: Zryan Tech Store
  - **Currency**: IQD
  - **Invoice Date**: February 10, 2024
  - **Lines**:
      - Product: Consulting Services
      - Quantity: 1
      - Unit Price: 2,190,000 IQD
      - Tax: VAT 10%
      - Subtotal: 2,190,000 IQD
      - Tax Amount: 219,000 IQD
      - Total: 2,409,000 IQD

**Expected Outcomes**:

**Customer Invoice Creation and Posting**:

  - Invoice Number: INV-002 (assigned upon posting)
  - Customer: Zryan Tech Store
  - Currency: IQD (base currency)
  - Total Amount: 2,409,000 IQD

**Journal Entry Created**:

```
Date: February 10, 2024 | Reference: INV-002
Dr. Accounts Receivable [120101]           2,409,000 IQD
    Cr. Consulting Revenue [430101]                    2,190,000 IQD
    Cr. VAT Payable [220101]                             219,000 IQD
```

**Account Balances After Posting (Multi-Currency)**:

  - Accounts Receivable [120101]:
      - USD: $3,960.00 (Hawre Trading Group)
      - IQD: 2,409,000 IQD (Zryan Tech Store)
      - Total in base currency: 8,193,600 IQD
  - Consulting Revenue [430101]: 2,190,000 IQD (Credit Balance)
  - VAT Payable [220101]:
      - From USD sales: 525,600 IQD ($360 × 1460)
      - From IQD sales: 219,000 IQD
      - Total: 744,600 IQD (Credit Balance)

**Multi-Currency Accounting Impact**:

  - **Base Currency Reporting**: All amounts converted to IQD for financial statements
  - **Foreign Currency Tracking**: USD amounts maintained in original currency
  - **Exchange Rate**: Current rate 1 USD = 1,460 IQD applied
  - **No Currency Gain/Loss**: Transaction in base currency

**Service Revenue Recognition**:

  - **Revenue Type**: Service (no inventory impact)
  - **Recognition**: Immediate (services performed)
  - **No COGS**: Service has no direct cost of goods sold

**Customer Management**:

  - Zryan Tech Store receivable balance: 2,409,000 IQD
  - Due date: March 12, 2024 (30 days)
  - Currency: IQD (local currency)

**Consolidated VAT Position**:

  - **VAT Receivable**: 1,168,000 IQD ($800 × 1460)
  - **VAT Payable**: 744,600 IQD
  - **Net VAT Receivable**: 423,400 IQD (company owes less tax)

-----

## Phase 6: Payment Processing & Bank Reconciliation

### Step 10: Receive Customer Payment (Partial)

**Business Context**: Hawre Trading Group pays $2,000 of their $3,960 invoice.

**Action**: Create payment

  - **Payment Type**: Inbound
  - **Journal**: Bank (USD)
  - **Amount**: $2,000
  - **Date**: February 15, 2024
  - **Link to**: Invoice from Step 8 (partial payment)

**Expected Outcomes**:

**Payment Creation and Confirmation**:

  - Payment Number: PAY-002 (assigned upon confirmation)
  - Payment Type: Inbound
  - Amount: $2,000.00
  - Status: Confirmed

**Journal Entry Created**:

```
Date: February 15, 2024 | Reference: PAY-002
Dr. Bank Account (USD) [110101]            $2,000.00
    Cr. Accounts Receivable [120101]                   $2,000.00
```

**Account Balances After Posting**:

  - Bank Account (USD) [110101]: $36,000.00 (increased from $34,000)
  - Accounts Receivable [120101]:
      - USD: $1,960.00 (reduced from $3,960)
      - IQD: 2,409,000 IQD (unchanged)
      - Total in base currency: 5,331,600 IQD

**Invoice Payment Allocation**:

  - **Invoice INV-001 Status**: Partially Paid
  - **Original Amount**: $3,960.00
  - **Payment Applied**: $2,000.00
  - **Outstanding Balance**: $1,960.00
  - **Payment Percentage**: 50.5%

**Customer Account Status**:

  - Hawre Trading Group balance: $1,960.00 outstanding
  - Payment history: $2,000.00 received on Feb 15, 2024
  - Days outstanding: 10 days (within terms)

**Cash Flow Impact**:

  - **Operating Cash Flow**: +$2,000.00 (collection from customer)
  - **Cash and Cash Equivalents**: Increased by $2,000.00

**Aging Analysis Impact**:

  - **Current (0-30 days)**: $1,960.00 (remaining balance)
  - **Collection Rate**: 50.5% of invoice collected within 10 days

### Step 11: Foreign Exchange Gain/Loss Testing

**Business Context**: Exchange rate changes between USD invoice creation and payment, creating foreign exchange gain on foreign currency transaction.

**Setup**: Change system exchange rate from 1:1460 to 1:1475 before processing remaining payment from Hawre Trading Group.

**Action**: Process remaining payment for Hawre Trading Group's USD invoice when rate has changed

  - **Original Invoice**: $3,960 USD (created at rate 1:1460 = 5,781,600 IQD)
  - **Previous Payment**: $2,000 USD (paid at rate 1:1460)
  - **Remaining Balance**: $1,960 USD (recorded at 2,861,600 IQD)
  - **Payment Date**: February 20, 2024
  - **New Exchange Rate**: 1 USD = 1,475 IQD
  - **Payment Amount**: $1,960 USD

**Expected Outcomes**:

**Payment Processing with FX Gain**:

  - Payment Number: PAY-003
  - Payment Type: Inbound
  - Amount: $1,960 USD
  - Status: Confirmed

**Compound Journal Entry (Payment with FX Gain)**:

```
Date: February 20, 2024 | Reference: PAY-003
Dr. Bank Account (USD) [110101]            2,891,000 IQD  ($1,960 × 1475)
    Cr. Accounts Receivable [120101]                   2,861,600 IQD  (original value)
    Cr. Realized FX Gain [610301]                         29,400 IQD  (gain)
```

**Foreign Exchange Calculation**:

  - **Original Receivable Value**: $1,960 × 1,460 = 2,861,600 IQD
  - **Cash Received Value**: $1,960 × 1,475 = 2,891,000 IQD
  - **Realized FX Gain**: 2,891,000 - 2,861,600 = 29,400 IQD

**Accounting Treatment**:

  - **Foreign Currency Exposure**: Only USD transactions have FX risk
  - **Base Currency (IQD)**: No FX exposure on IQD transactions
  - **Realized Gain**: Recognized when foreign currency is converted
  - **Standard Practice**: Gain credited to income account

**Multi-Currency Impact**:

  - **Realized FX Gain**: 29,400 IQD (recognized in income)
  - **Customer Balance**: Fully settled
  - **Exchange Rate Tracking**: Historical vs. current rates properly handled

### Step 12: Import Bank Statement

**Business Context**: Import bank statement showing the $2,000 deposit and bank fees.

**Action**: Create bank statement

  - **Journal**: Bank (USD)
  - **Date**: February 15, 2024
  - **Starting Balance**: $34,000
  - **Ending Balance**: $35,995
  - **Lines**:
      - Line 1: +$2,000 "Customer payment"
      - Line 2: -$5 "Bank service fee"

**Expected Outcomes**:

**Bank Statement Creation**:

  - Statement Reference: BS-001
  - Statement Date: February 15, 2024
  - Currency: USD
  - Starting Balance: $34,000.00
  - Ending Balance: $35,995.00

**Bank Statement Lines Created**:

1.  **Line 1**: +$2,000.00 "Customer payment" (Unreconciled)
2.  **Line 2**: -$5.00 "Bank service fee" (Unreconciled)

**Reconciliation Status**:

  - **Book Balance**: $36,000.00 (per accounting records)
  - **Bank Balance**: $35,995.00 (per bank statement)
  - **Difference**: $5.00 (unrecorded bank fee)

**Outstanding Items Analysis**:

  - **Deposits in Transit**: $0.00
  - **Outstanding Checks**: $0.00
  - **Bank Charges**: $5.00 (not yet recorded in books)
  - **Interest Earned**: $0.00

**Reconciliation Requirements**:

1.  Match $2,000.00 deposit with Payment PAY-002
2.  Record $5.00 bank service fee in accounting records
3.  Verify final reconciled balance matches bank statement

**Bank Statement Line Details**:

  - Line 1: Amount $2,000.00, Date Feb 15, Description "Customer payment", Status: Unreconciled
  - Line 2: Amount -$5.00, Date Feb 15, Description "Bank service fee", Status: Unreconciled

### Step 13: Bank Reconciliation

**Business Context**: Reconcile the customer payment and record bank fees.

**Actions**:

1.  **Reconcile payment**: Match $2,000 bank line with payment from Step 10
2.  **Record bank fee**: Create journal entry for $5 bank charge

**Expected Outcomes**:

**Step 1: Payment Reconciliation**:

  - Payment PAY-002 status: Changed from "Confirmed" to "Reconciled"
  - Bank statement line 1: Marked as reconciled
  - Reconciliation date: February 15, 2024

**Step 2: Bank Fee Recording**:

**Journal Entry Created**:

```
Date: February 15, 2024 | Reference: MISC-001
Dr. Bank Charges Expense [530401]          $5.00
    Cr. Bank Account (USD) [110101]                    $5.00
```

**Account Balances After Reconciliation**:

  - Bank Account (USD) [110101]: $35,995.00 (reduced by $5.00 fee)
  - Bank Charges Expense [530401]: $5.00 (Debit Balance)

**Reconciliation Summary**:

  - **Book Balance (Before)**: $36,000.00
  - **Less: Bank Charges**: $5.00
  - **Adjusted Book Balance**: $35,995.00
  - **Bank Statement Balance**: $35,995.00
  - **Difference**: $0.00 ✓ (Reconciled)

**Bank Statement Status**:

  - Line 1 ($2,000.00): Reconciled with Payment PAY-002
  - Line 2 (-$5.00): Reconciled with Journal Entry MISC-001
  - Statement Status: Fully Reconciled

**Cash Flow Impact**:

  - **Operating Cash Flow**: -$5.00 (bank service charges)
  - **Net Cash Position**: Accurately reflects bank balance

**Internal Controls Verification**:

  - All bank transactions properly supported
  - No unexplained differences
  - Audit trail complete from source documents to bank statement

-----

## Phase 7: Period-End Procedures

### Step 14: Process All Outstanding Depreciation

**Business Context**: Ensure all assets have current depreciation recorded.

**Action**: Run depreciation processing for February

  - **Assets**: Office Equipment
  - **Period**: February 2024

**Expected Outcomes**:

**February Depreciation Processing**:

  - Depreciation Entry Status: Changed from "Draft" to "Posted"
  - Amount: $75.00
  - Depreciation Date: February 29, 2024

**Journal Entry Created Automatically**:

```
Date: February 29, 2024 | Reference: DEPR/Office Equipment/2024-02
Dr. Depreciation Expense [530301]          $75.00
    Cr. Acc. Depreciation - Office Equipment [150199]  $75.00
```

**Cumulative Account Balances**:

  - Depreciation Expense [530301]: $150.00 (Jan $75 + Feb $75)
  - Acc. Depreciation - Office Equipment [150199]: $150.00 (Credit Balance)
  - Office Equipment [150101]: $5,000.00 (unchanged - original cost)

**Asset Valuation Update**:

  - **Gross Asset Value**: $5,000.00
  - **Accumulated Depreciation**: $150.00 (2 months)
  - **Net Book Value**: $4,850.00
  - **Remaining Depreciable Amount**: $4,350.00
  - **Remaining Useful Life**: 58 months

**Year-to-Date Depreciation Summary**:

  - January 2024: $75.00
  - February 2024: $75.00
  - **Total YTD**: $150.00
  - **Annual Depreciation Rate**: $900.00 ($75 × 12 months)

### Step 14.5: Unrealized Foreign Exchange Gain/Loss (New)

**Business Context**: At the end of the month, revalue open foreign currency balances to reflect current exchange rates on the financial statements.

**Action**: Run period-end unrealized FX gain/loss revaluation.

  - **Open Balance**: The Zryan Tech Store invoice (INV-002) for 2,409,000 IQD has an open Accounts Receivable balance in the base currency, so it is not revalued.
  - The Hawre Trading Group invoice (INV-001) is now fully paid after Step 11, so there is no open USD receivable to revalue.
  - **Open Payable**: The Hiwa Computer Center bill (BILL-002) for $8,800 is still open.
  - **New Exchange Rate**: Assume the rate at Feb 29, 2024 is 1 USD = 1450 IQD (down from 1460).

**Expected Outcomes**:

**Unrealized FX Gain/Loss Calculation**:

  - **Original Payable Value**: $8,800 × 1,460 = 12,848,000 IQD
  - **New Payable Value**: $8,800 × 1,450 = 12,760,000 IQD
  - **Unrealized FX Gain**: 12,848,000 - 12,760,000 = 88,000 IQD (The liability has decreased in value, which is a gain for the company).

**Adjusting Journal Entry Created**:

```
Date: February 29, 2024 | Reference: FX-UNREAL-2024-02
Dr. Accounts Payable [210101]                 88,000 IQD
    Cr. Unrealized FX Gain [Income]                      88,000 IQD
```

This entry reduces the liability on the balance sheet and recognizes the gain on the income statement, ensuring a true and fair view at period-end.

-----

## Phase 8: Adjustments & Corrections

### Step 15: Create Credit Note for Return

**Business Context**: Customer returns 1 wireless router due to defect.

**Action**: Create credit note

  - **Customer**: Hawre Trading Group
  - **Reference to**: Invoice from Step 8
  - **Lines**:
      - Product: Wireless Router
      - Quantity: 1 (return)
      - Unit Price: $1,200
      - Tax: VAT 10%
      - Total Credit: $1,320

**Expected Outcomes**:

**Credit Note Creation and Posting**:

  - Credit Note Number: CN-001 (assigned upon posting)
  - Customer: Hawre Trading Group
  - Reference Invoice: INV-001
  - Total Credit: $1,320.00

**Primary Journal Entry (Sales Return)**:

```
Date: February 25, 2024 | Reference: CN-001
Dr. Sales Discounts & Returns [490101]    $1,200.00
Dr. VAT Payable [220101]                     $120.00
    Cr. Accounts Receivable [120101]                  $1,320.00
```

**Secondary Journal Entry (Inventory Return)**:

```
Date: February 25, 2024 | Reference: CN-001-INV
Dr. Inventory [130101]                     $800.00
    Cr. Cost of Goods Sold [510101]                   $800.00
```

**Account Balances After Posting**:

  - Accounts Receivable [120101]: Balance fully paid, this credit will be outstanding.
  - Sales Discounts & Returns [490101]: $1,200.00 (Debit Balance - Contra Revenue)
  - VAT Payable [220101]: $240.00 ($360 - $120)
  - Inventory [130101]: $6,400.00 ($5,600 + $800)
  - Cost of Goods Sold [510101]: $1,600.00 ($2,400 - $800)

**Inventory Management Impact**:

  - **Stock Move Created (Return)**:
      - Product: Wireless Router
      - Movement Type: Incoming (Customer Return → Inventory)
      - Quantity: +1 unit
      - Unit Cost: $800.00 (original cost basis)
      - Total Value: $800.00

**Updated Inventory Position**:

  - **Total Quantity**: 8 units (7 + 1 returned)
  - **Total Value**: $6,400.00 (8 × $800)
  - **Average Cost**: $800.00 per unit (unchanged)

### Step 15.5: Inventory Adjustment/Write-Off (New)

**Business Context**: A physical inventory count reveals that 2 of the 8 wireless routers in stock are damaged beyond repair and must be scrapped.

**Action**: Create inventory adjustment

  - **Date**: February 26, 2024
  - **Product**: Wireless Router
  - **Quantity to Write-Off**: 2 units
  - **Cost per unit**: $800

**Expected Outcomes**:

**Journal Entry Created for Inventory Write-Off**:

```
Date: February 26, 2024 | Reference: INV-ADJ-001
Dr. Inventory Loss/Scrap Expense [510105]     $1,600.00
    Cr. Inventory [130101]                              $1,600.00
```

**Account Balances After Posting**:

  - Inventory [130101]: $4,800.00 (Debit Balance - reduced from $6,400)
  - Inventory Loss/Scrap Expense [510105]: $1,600.00 (Debit Balance)

**Inventory Position After Adjustment**:

  - **Remaining Quantity**: 6 units (8 - 2)
  - **Remaining Value**: $4,800.00 (6 × $800)

**Income Statement Impact**:

  - Expenses increase by $1,600, correctly recognizing the loss of asset value within the period.

### Step 16: Allowance Method for Bad Debt (GAAP Compliant)

**Business Context**: Estimate bad debt expense using allowance method for better matching principle compliance.

**Action 1**: Create period-end adjusting entry for estimated bad debt

  - **Journal**: Miscellaneous Operations
  - **Date**: February 28, 2024
  - **Estimated Bad Debt**: 2% of outstanding receivables
  - **Lines**:
      - Debit: Bad Debt Expense - $500
      - Credit: Allowance for Doubtful Accounts - $500

**Action 2**: Write-off specific uncollectible account

  - **Journal**: Miscellaneous Operations
  - **Date**: March 5, 2024
  - **Lines**:
      - Debit: Allowance for Doubtful Accounts - $500
      - Credit: Accounts Receivable - $500

**Expected Outcomes**:

**Action 1 - Allowance Creation**:

**Adjusting Journal Entry Created**:

```
Date: February 28, 2024 | Reference: MISC-002
Dr. Bad Debt Expense [530601]              $500.00
    Cr. Allowance for Doubtful Accounts [120199]      $500.00
```

**Account Balances After Allowance**:

  - Bad Debt Expense [530601]: $500.00 (Debit Balance)
  - Allowance for Doubtful Accounts [120199]: $500.00 (Credit Balance - Contra Asset)
  - Accounts Receivable [120101]: Unchanged by this entry.

**Action 2 - Specific Write-off**:

**Write-off Journal Entry**:

```
Date: March 5, 2024 | Reference: MISC-003
Dr. Allowance for Doubtful Accounts [120199]  $500.00
    Cr. Accounts Receivable [120101]                  $500.00
```

**Final Account Balances**:

  - Accounts Receivable [120101]: Gross amount reduced
  - Allowance for Doubtful Accounts [120199]: Utilized and reduced
  - Bad Debt Expense [530601]: No change - expense already recognized

### Step 17: Accrued Expenses Testing

**Business Context**: Record expenses incurred but not yet billed (e.g., utilities used in February, bill arrives in March).

**Action 1**: Create period-end accrual for estimated utilities

  - **Journal**: Miscellaneous Operations
  - **Date**: February 29, 2024
  - **Estimated Amount**: $200 USD
  - **Lines**:
      - Debit: Utilities Expense - $200
      - Credit: Accrued Liabilities - $200

**Action 2**: Reverse accrual when actual bill arrives

  - **Journal**: Miscellaneous Operations
  - **Date**: March 5, 2024
  - **Lines**:
      - Debit: Accrued Liabilities - $200
      - Credit: Utilities Expense - $200

**Action 3**: Record actual vendor bill

  - **Vendor**: Local Utility Company
  - **Date**: March 5, 2024
  - **Actual Amount**: $195 USD
  - **Lines**:
      - Debit: Utilities Expense - $195
      - Credit: Accounts Payable - $195

**Expected Outcomes**:

**Accrual Entry (Feb 29)**:

```
Date: February 29, 2024 | Reference: MISC-004
Dr. Utilities Expense [530501]             $200.00
    Cr. Accrued Liabilities [220501]                  $200.00
```

**Reversal Entry (Mar 5)**:

```
Date: March 5, 2024 | Reference: MISC-005
Dr. Accrued Liabilities [220501]           $200.00
    Cr. Utilities Expense [530501]                    $200.00
```

**Actual Bill Entry (Mar 5)**:

```
Date: March 5, 2024 | Reference: BILL-003
Dr. Utilities Expense [530501]             $195.00
    Cr. Accounts Payable [210101]                     $195.00
```

-----

## Phase 9: Advanced Inventory Costing (FIFO Verification)

### Step 17: Second Purchase at Different Cost
**Business Context**: Purchase additional routers at a higher cost to test FIFO costing method.

**Action**: Create vendor bill
- **Vendor**: Hiwa Computer Center
- **Currency**: USD
- **Bill Date**: February 20, 2024
- **Lines**:
  - Product: Wireless Router
  - Quantity: 5
  - Unit Price: $810 (higher cost)
  - Tax: VAT 10%
  - Subtotal: $4,050
  - Tax Amount: $405
  - Total: $4,455

**Expected Outcomes**:

**Journal Entry Created**:
```
Date: February 20, 2024 | Reference: BILL-003
Dr. Inventory [130101]                     $4,050.00
Dr. VAT Receivable [120102]                  $405.00
    Cr. Accounts Payable [210101]                     $4,455.00
```

**Inventory Costing Layers After Purchase**:
- **Layer 1**: 8 units @ $800.00 = $6,400.00 (from previous transactions)
- **Layer 2**: 5 units @ $810.00 = $4,050.00 (new purchase)
- **Total Inventory**: 13 units valued at $10,450.00
- **Weighted Average Cost**: $803.85 per unit (for comparison)

**Account Balances**:
- Inventory [130101]: $10,450.00
- VAT Receivable [120102]: $1,573.00 ($1,168 + $405)
- Accounts Payable [210101]: $13,255.00 ($8,800 + $4,455)

### Step 18: Sale to Test FIFO Costing
**Business Context**: Sell 6 routers to verify FIFO method uses oldest costs first.

**Action**: Create customer invoice
- **Customer**: Zryan Tech Store
- **Currency**: USD
- **Invoice Date**: February 22, 2024
- **Lines**:
  - Product: Wireless Router
  - Quantity: 6
  - Unit Price: $1,250
  - Tax: VAT 10%
  - Subtotal: $7,500
  - Tax Amount: $750
  - Total: $8,250

**Expected Outcomes**:

**Sales Journal Entry**:
```
Date: February 22, 2024 | Reference: INV-003
Dr. Accounts Receivable [120101]           $8,250.00
    Cr. Product Sales [410101]                        $7,500.00
    Cr. VAT Payable [220101]                            $750.00
```

**FIFO COGS Calculation**:
- **First 6 units from Layer 1**: 6 × $800.00 = $4,800.00
- **Remaining Layer 1**: 2 units @ $800.00 = $1,600.00
- **Layer 2 unchanged**: 5 units @ $810.00 = $4,050.00

**COGS Journal Entry**:
```
Date: February 22, 2024 | Reference: INV-003-COGS
Dr. Cost of Goods Sold [510101]            $4,800.00
    Cr. Inventory [130101]                             $4,800.00
```

**Inventory Position After FIFO Sale**:
- **Remaining Layer 1**: 2 units @ $800.00 = $1,600.00
- **Layer 2**: 5 units @ $810.00 = $4,050.00
- **Total Remaining**: 7 units valued at $5,650.00
- **FIFO Verification**: Oldest costs (Layer 1) used first ✓

---

## Phase 10: Credit Note Application Lifecycle

### Step 19: Apply Credit Note to New Invoice
**Business Context**: Customer places new order, and we apply their existing credit balance.

**Action**: Create new customer invoice
- **Customer**: Hawre Trading Group (has $1,320 credit from Step 15)
- **Currency**: USD
- **Invoice Date**: February 25, 2024
- **Lines**:
  - Product: CAT6 Ethernet Cable
  - Quantity: 50
  - Unit Price: $35
  - Tax: VAT 10%
  - Subtotal: $1,750
  - Tax Amount: $175
  - Total: $1,925

**Expected Outcomes**:

**Invoice Creation**:
```
Date: February 25, 2024 | Reference: INV-004
Dr. Accounts Receivable [120101]           $1,925.00
    Cr. Product Sales [410101]                        $1,750.00
    Cr. VAT Payable [220101]                            $175.00
```

**Credit Note Application**:
- **Invoice Total**: $1,925.00
- **Less: Credit Applied**: $1,320.00
- **Net Amount Due**: $605.00

**Credit Application Entry**:
```
Date: February 25, 2024 | Reference: CREDIT-APP-001
Dr. Accounts Receivable [120101]           $1,320.00
    Cr. Accounts Receivable [120101]                   $1,320.00
```

**Final Customer Balance**:
- Hawre Trading Group: $605.00 net receivable
- Credit note fully utilized
- Customer payment required: $605.00

---

## Phase 11: VAT Settlement and Tax Reporting

### Step 20: Generate VAT Report and Settlement
**Business Context**: Month-end VAT reconciliation and payment to tax authority.

**Action**: Generate VAT report for February 2024

**Expected Outcomes**:

**VAT Report Summary (in USD)**:
```
VAT RECEIVABLE (Input VAT):
  Purchase from Hiwa Computer Center    $1,205.00  ($800 + $405)
  Total Input VAT                       $1,205.00

VAT PAYABLE (Output VAT):
  Sales to Hawre Trading Group           $240.00   ($360 - $120 return)
  Sales to Zryan Tech Store              $750.00
  Service to Zryan Tech Store            $150.00   (219,000 IQD ÷ 1460)
  Sales to Hawre Trading Group (new)    $175.00
  Total Output VAT                     $1,315.00

NET VAT PAYABLE                          $110.00
```

**VAT Settlement Journal Entry**:
```
Date: February 28, 2024 | Reference: VAT-SETTLE-001
Dr. VAT Receivable [120102]            $1,205.00
Dr. VAT Payable [220101]                 $110.00
    Cr. VAT Payable [220101]                          $1,315.00
```

**Final VAT Position**:
- VAT Receivable: $0.00 (cleared)
- VAT Payable: $110.00 (net amount due to tax authority)

---

## Phase 12: Complex Bank Reconciliation

### Step 21: Complex Bank Reconciliation with Timing Differences
**Business Context**: Test deposits in transit and outstanding checks.

**Action**: Create complex bank statement scenario
- **Date**: February 28, 2024
- **Starting Balance**: $36,995.00
- **Ending Balance**: $38,500.00

**Bank Statement Lines**:
1. +$2,000.00 "Customer deposit" (Feb 26)
2. -$495.00 "Check #001 to vendor" (Feb 27)

**Book Transactions Not on Statement**:
- Payment received Feb 28: $605.00 (Hawre Trading Group - deposit in transit)
- Check issued Feb 28: $1,000.00 (to Hiwa Computer Center - outstanding check)

**Expected Outcomes**:

**Reconciliation Analysis**:
```
BANK BALANCE PER STATEMENT                 $38,500.00
Add: Deposits in Transit                      $605.00
Less: Outstanding Checks                   ($1,000.00)
ADJUSTED BANK BALANCE                      $38,105.00

BOOK BALANCE PER RECORDS                   $38,105.00
DIFFERENCE                                      $0.00
```

**Reconciliation Items**:
- **Reconciled Items**: Customer deposit $2,000, Vendor check $495
- **Timing Differences**: Deposit in transit $605, Outstanding check $1,000
- **Adjustments Required**: None (all items properly recorded)

---

## Phase 13: Lock Date & Immutability Testing

### Step 22: Set Lock Date

**Business Context**: Lock January 2024 to prevent backdated entries.

**Action**: Set fiscal lock date to January 31, 2024

**Expected Outcomes**:

**Lock Date Implementation**:

  - **Lock Date Set**: January 31, 2024
  - **Effective Date**: February 1, 2024 (earliest allowed posting date)
  - **Scope**: All journal entries, invoices, bills, payments

### Step 23: Attempt Backdated Entry (Should Fail)

**Business Context**: Try to create a journal entry dated in January.

**Action**: Attempt to create journal entry dated January 15, 2024

**Expected Outcomes**:

**System Response**:

  - **Entry Rejected**: Transaction not saved to database
  - **Error Message**: "Cannot post transactions dated before the lock date of January 31, 2024"

-----

## Phase 10: Final Reporting & Verification

### Step 20: Generate Financial Reports

**Business Context**: Prepare month-end financial statements.

**Actions**: Generate reports for February 2024

1.  **Balance Sheet**
2.  **Profit & Loss Statement**
3.  **Trial Balance**

**Expected Outcomes (Recalculated with new steps)**:

**Income Statement - Year-to-Date February 29, 2024 (in IQD)**:

```
REVENUE
  Product Sales                         5,256,000  ($3,600 × 1460)
  Consulting Revenue                    2,190,000
  Less: Sales Returns                  (1,752,000) ($1,200 × 1460)
  Net Revenue                           5,694,000

OTHER INCOME
  Realized FX Gain                         29,400  (exchange rate change - payment)
  Unrealized FX Gain                       88,000  (exchange rate change - period end)
  Total Revenue                         5,811,400

COST OF GOODS SOLD
  Cost of Goods Sold                    3,504,000  ($2,400 × 1460)
  Less: COGS Reversal (Returns)        (1,168,000) ($800 × 1460)
  Net Cost of Goods Sold                2,336,000
  Gross Profit                          3,475,400

OPERATING EXPENSES
  Depreciation Expense                    219,000  ($150 × 1460 - 2 months)
  Bank Charges Expense                      7,300  ($5 × 1460)
  Bad Debt Expense                        730,000  ($500 × 1460)
  Utilities Expense                       292,000  ($200 × 1460 - Accrual)
  Inventory Loss/Scrap Expense          2,336,000  ($1,600 x 1460 - Write-off)
  Total Operating Expenses              3,584,300

NET INCOME / (LOSS)                      (108,900)
```

**Balance Sheet as of February 29, 2024 (in IQD)**:
The final balance sheet would be adjusted to reflect all the new transactions. The key is that the accounting equation **Assets = Liabilities + Equity** remains in balance, incorporating the net loss for the period into equity.

-----

### Step 24: Audit Trail Verification

**Business Context**: Verify complete audit trail and immutability.

**Verification Points**:

1.  **Cryptographic Hashing**: All journal entries have valid hashes
2.  **Sequential Numbering**: No gaps in document sequences
3.  **Immutability**: Posted documents cannot be edited
4.  **Multi-Currency**: Exchange rates properly recorded
5.  **Stock Movements**: Inventory quantities reconcile (now showing 6 units)
6.  **Asset Depreciation**: Schedules are accurate
7.  **Tax Calculations**: VAT amounts are correct
8.  **Bank Reconciliation**: All items properly matched

**Expected Outcomes**:
- Complete audit trail from source documents to financial statements
- All balances reconcile across modules including new transactions
- No data integrity issues
- System demonstrates enterprise-grade accounting controls
- FIFO costing method properly validated
- Credit note lifecycle fully tested
- VAT settlement process verified
- Complex bank reconciliation scenarios handled

---

## Summary of Key Accounting Principles Tested

1. **Double-Entry Bookkeeping**: Every transaction properly debits and credits
2. **Multi-Currency Accounting**: Foreign currency transactions handled correctly
3. **Revenue Recognition**: Sales recorded when earned, returns properly handled
4. **Matching Principle**: Expenses matched with related revenues
5. **Asset Capitalization**: Fixed assets properly recorded and depreciated
6. **Inventory Costing**: FIFO method verified with multiple cost layers
7. **Accrual Accounting**: Transactions recorded when incurred
8. **Internal Controls**: Lock dates, immutability, audit trails
9. **Financial Reporting**: Accurate statements generated with all adjustments
10. **Regulatory Compliance**: VAT calculations, settlement, and reporting

---

## Enhanced Testing Coverage

This comprehensive scenario now includes advanced testing for:

### 1. **Complete Credit Note Lifecycle** (Steps 15 & 19)
- Credit note creation for product returns
- Credit balance application to subsequent invoices
- Customer account management with credit balances
- Full revenue reversal and inventory return processing

### 2. **FIFO Inventory Costing Verification** (Steps 17-18)
- Multiple purchase batches at different unit costs
- Proper FIFO cost layer management
- Verification that oldest costs are used first in COGS calculations
- Inventory valuation accuracy across cost layers

### 3. **VAT Settlement and Tax Reporting** (Step 20)
- Period-end VAT reconciliation and reporting
- Input VAT vs. Output VAT netting process
- Tax liability calculation and settlement entries
- Compliance with tax authority reporting requirements

### 4. **Complex Bank Reconciliation** (Step 21)
- Deposits in transit (timing differences)
- Outstanding checks and payments
- Real-world reconciliation scenarios beyond simple matching
- Proper handling of book vs. bank timing differences

### 5. **Multi-Currency Integration Throughout**
- Consistent exchange rate application across all transactions
- Base currency reporting with foreign currency tracking
- Currency conversion accuracy in financial statements
- Multi-currency VAT calculations and settlements

These enhancements ensure the testing scenario covers the complete accounting lifecycle from initial transactions through period-end procedures, providing a thorough validation of enterprise-grade accounting functionality suitable for Iraqi business operations.
