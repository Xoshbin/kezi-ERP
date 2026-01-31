---
title: Vendor Bills
icon: heroicon-o-document-text
order: 14
---


This comprehensive guide explains how vendor bills work in your accounting system, covering creation, approval workflows, payment processing, and expense recognition. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is a Vendor Bill?

A vendor bill is a commercial document received from suppliers that establishes a legal obligation to pay for goods received or services obtained, creating proper expense recognition and accounts payable tracking.

- **Records a Purchase**: Documents goods received or services obtained from vendors
- **Establishes Liability**: Creates legal and accounting obligation to pay the vendor
- **Recognizes Expenses**: Creates accounting entries for costs under accrual accounting
- **Tracks Payables**: Establishes amounts owed to vendors in accounts payable

**Accounting Impact**: Posted bills increase expenses and accounts payable, following the accrual accounting principle that recognizes expenses when incurred, not when cash is paid.

---

## System Requirements

### Vendor Configuration
- **Vendor Records**: Vendor profiles must be created with payment information
- **Payment Terms**: Payment terms must be configured for vendor agreements
- **Tax Setup**: Tax rates and rules must be configured for applicable purchases
- **Currency Support**: Invoice currencies must be active and configured

### Prerequisites
1. **Vendor Setup**: Vendor must exist in the system with complete information
2. **Product/Service Configuration**: Items being purchased must be set up if using catalog
3. **Expense Accounts**: Appropriate expense accounts must be configured
4. **User Permissions**: Access to purchasing and accounting features required

---

## Where to find it in the UI

Navigate to **Accounting → Purchases → Vendor Bills**

Vendor bills also appear in:
- **Create Action**: "Create" button for new bill entry
- **Vendor Records**: Related bills section showing purchase history
- **Payment Processing**: Available bills for payment in banking section

**Tip**: The header's Help/Docs button opens this guide.

---

## Bill Lifecycle

### 1. Draft Status
- Bill is being prepared
- Can be edited or deleted
- No accounting impact
- Not yet approved for payment

### 2. Posted Status
- Bill is finalized and approved
- Creates journal entries
- Cannot be edited (only adjustment documents)
- Available for payment

### 3. Paid Status
- Vendor payment made
- Accounts payable cleared
- Transaction complete

### 4. Cancelled Status
- Bill is cancelled
- No accounting impact

---

## Creating a Vendor Bill

Navigate to **Accounting → Purchases → Vendor Bills → Create**

### Step 1: Bill Header

**Vendor**: Select the vendor from the partner list
**Bill Date**: Enter the vendor's invoice date
**Accounting Date**: Date for accounting recognition
**Due Date**: Payment deadline (auto-calculated from payment terms)
**Currency**: Select bill currency (defaults to vendor currency)
**Payment Terms**: Choose payment conditions ([Payment Terms Guide](payment-terms-guide.md))
**Vendor Reference**: Enter vendor's invoice number
**Reference**: Internal reference or purchase order number

### Step 2: Bill Lines

Click **Add Line** to add purchased items:

#### Product Lines
**Product**: Select from product catalog
**Description**: Auto-filled from product (editable)
**Quantity**: Number of units purchased
**Unit Cost**: Cost per unit
**Taxes**: Select applicable tax rates (VAT, sales tax)
**Account**: Expense or asset account (auto-filled from product)

#### Service Lines
**Service**: Select service item
**Description**: Describe the service received
**Hours/Units**: Quantity of service
**Rate**: Hourly or unit rate
**Taxes**: Select applicable tax rates
**Account**: Service expense account

#### Expense Lines
**Description**: Enter expense description
**Amount**: Total expense amount
**Account**: Select appropriate expense account
**Asset Category**: For capital expenditures, select asset category to create an asset
**Taxes**: Select applicable taxes

### Step 3: Taxes and Totals

**Tax Calculation**: System automatically calculates taxes based on:
- Product/service tax configuration
- Vendor tax settings
- Tax rules and fiscal positions

**Bill Totals**:
- **Subtotal**: Sum of all line amounts before tax
- **Tax Amount**: Total tax calculated (often recoverable VAT)
- **Total Amount**: Final amount owed to vendor

### Step 4: Additional Information

**Payment Terms**: Defines when payment is due
**Tax Rules**: Tax handling based on vendor location
**Journal**: Purchase journal for posting (usually auto-selected)
**Notes**: Additional terms or delivery instructions

---

## Advanced Bill Features

### Multi-Currency Bills

When receiving bills in foreign currency:

1. **Bill Currency**: Use vendor's invoice currency
2. **Exchange Rate**: System uses current rate or manual entry
3. **Company Currency**: System converts for accounting records
4. **Exchange Differences**: Handled automatically at payment

**Example**: Receive bill for $2,000 USD when company uses IQD
- Bill Amount: $2,000 USD
- Exchange Rate: 1 USD = 1,310 IQD
- Accounting Amount: 2,620,000 IQD

### Payment Installments

The system supports payment installments based on payment terms:

1. **Payment Terms Configuration**: Set up installment schedules
2. **Automatic Generation**: Installments created when bill is posted
3. **Multiple Due Dates**: Different amounts due on different dates
4. **Payment Tracking**: Track payments against specific installments

### Tax Handling

**Input VAT**: Tax paid to vendors (often recoverable)
**Withholding Tax**: Tax deducted from vendor payments
**Import Duties**: Additional taxes on imported goods
**Tax Exemptions**: Some purchases may be tax-exempt

---

## Bill Posting Process

### Before Posting - Review Checklist

✅ **Vendor Information**: Correct vendor selected
✅ **Bill Details**: Vendor invoice number and date accurate
✅ **Line Items**: All purchased items included with correct quantities
✅ **Pricing**: Unit costs and totals match vendor invoice
✅ **Taxes**: Appropriate tax rates applied
✅ **Accounts**: Correct expense/asset accounts assigned
✅ **Approval**: Required approvals obtained

### Posting the Bill

1. **Save as Draft**: Save for review and approval
2. **Review**: Check all details against vendor invoice
3. **Approve**: Obtain necessary approvals
4. **Post**: Click "Post" to finalize the bill

**⚠️ Important**: Once posted, bills cannot be edited. Use adjustment documents for corrections.

### Journal Entry Created

When posted, the system creates:
```
Dr. Office Supplies Expense    500,000 IQD
Dr. Input VAT                   50,000 IQD
    Cr. Accounts Payable            550,000 IQD
```

---

## Managing Posted Bills

### Payment Processing

**From Bill**: Click "Register Payment" on the bill
**From Payments**: Create payment and link to bill
**Batch Payments**: Pay multiple bills in one transaction

For more information about payments, see the [Payments Guide](payments.md).

### Partial Payments

Vendors may accept partial payments:
1. Register payment for partial amount
2. Bill remains "Partially Paid"
3. Outstanding balance tracked automatically
4. Multiple payments can be applied

### Adjustment Documents

For returns, discounts, or corrections:
1. Click "Adjustment Document" on original bill
2. Select items to adjust
3. Specify adjustment amount
4. Post adjustment document
5. Apply to vendor account

---

## Purchase Categories and Accounts

### Inventory Purchases
**Account**: Inventory Asset Account
**Impact**: Increases inventory value
**Cost Recognition**: When goods are sold (COGS)

### Direct Expenses
**Account**: Operating Expense Accounts
**Impact**: Immediate expense recognition
**Examples**: Office supplies, utilities, rent

### Capital Expenditures
**Account**: Fixed Asset Accounts
**Impact**: Increases asset value
**Depreciation**: Expense recognized over asset life

### Services
**Account**: Service Expense Accounts
**Impact**: Immediate expense recognition
**Examples**: Consulting, maintenance, professional fees

---

## Approval Workflows

### Purchase Authorization Limits
**Department Managers**: Up to 5,000,000 IQD
**Directors**: Up to 25,000,000 IQD
**CFO**: Up to 100,000,000 IQD
**CEO**: Above 100,000,000 IQD

### Approval Process
1. **Requestor**: Creates bill in draft status
2. **Department Review**: Manager reviews and approves
3. **Finance Review**: Accounting verifies details
4. **Final Approval**: Authorized person approves for posting
5. **Posting**: Bill is posted and ready for payment

### Segregation of Duties
- **Bill Creation**: Purchasing department
- **Bill Approval**: Department managers
- **Payment Authorization**: Finance department
- **Payment Execution**: Treasury function

---

## Vendor Management

### Vendor Information
**Contact Details**: Address, phone, email
**Payment Terms**: Standard payment conditions
**Tax Information**: Tax ID, exemption status
**Banking Details**: Payment account information

### Vendor Performance
**Payment History**: Track payment timeliness
**Quality Metrics**: Rate product/service quality
**Pricing Analysis**: Compare costs over time
**Compliance**: Monitor certifications and licenses

---

## Recurring Bills

For regular vendor expenses:

### Setup Recurring Bill
1. Create template bill
2. Set recurrence pattern (monthly, quarterly, etc.)
3. Define start and end dates
4. Configure automatic posting

### Automatic Processing
- System generates bills automatically
- Approval workflow triggered
- Accounting entries created
- Payment scheduling available

---

## File Attachments

Vendor bills support file attachments:

### Supported File Types
- PDF documents
- Word documents (DOC, DOCX)
- Excel spreadsheets (XLS, XLSX)
- Images (JPG, PNG, GIF)
- Text files (TXT)

### Attachment Features
- Multiple files per bill
- Maximum 10MB per file
- Up to 10 files per bill
- Automatic file validation
- Secure file storage

---

## Reporting and Analysis

### Purchase Reports
**Purchases by Vendor**: Spending analysis by supplier
**Purchases by Category**: Expense category breakdown
**Purchases by Period**: Monthly/quarterly spending trends
**Tax Reports**: VAT/input tax reporting

### Payables Management
**Aged Payables**: Outstanding amounts by age
**Vendor Statements**: Account activity summaries
**Payment Scheduling**: Upcoming payment requirements
**Cash Flow Planning**: Payment timing analysis

---

## Best Practices

### Bill Verification
- **Match to Purchase Orders**: Verify against original requests
- **Check Delivery Receipts**: Confirm goods/services received
- **Validate Pricing**: Compare to agreed rates
- **Review Terms**: Ensure payment terms are correct

### Documentation
- **Vendor Invoices**: Attach original vendor documents
- **Delivery Receipts**: Keep proof of receipt
- **Approval Records**: Maintain approval documentation
- **Payment Records**: Link payment confirmations

### Internal Controls
- **Segregation of Duties**: Separate ordering, receiving, and payment
- **Authorization Limits**: Enforce spending limits
- **Regular Reviews**: Monitor vendor relationships
- **Audit Trails**: Maintain complete transaction records

---

## Common Scenarios

### Scenario 1: Office Supplies Purchase
Purchase office supplies for 500,000 IQD plus 10% VAT:

1. Select office supplies vendor
2. Add office supplies line (Amount: 500,000 IQD)
3. System calculates VAT: 50,000 IQD
4. Total bill: 550,000 IQD
5. Post bill

### Scenario 2: Professional Services
Legal services bill for 3,000,000 IQD:

1. Select law firm vendor
2. Add legal services line (Hours: 20, Rate: 150,000 IQD)
3. No VAT (professional services exempt)
4. Total bill: 3,000,000 IQD
5. Post bill

### Scenario 3: Equipment Purchase
Purchase computer equipment for 5,000,000 IQD:

1. Select equipment vendor
2. Add computer equipment line (Qty: 5, Cost: 1,000,000 IQD)
3. Assign to Fixed Assets account
4. Select asset category to create asset
5. Apply appropriate taxes
6. Post bill

---

## Troubleshooting

### Common Issues

**Problem**: Cannot post bill - validation errors
**Solution**:
- Check all required fields are completed
- Verify vendor information is complete
- Ensure all line items have accounts assigned

**Problem**: Tax calculation incorrect
**Solution**:
- Verify vendor tax configuration
- Check product tax settings
- Review tax rules and settings

**Problem**: Payment installment issues
**Solution**:
- Check payment terms configuration
- Verify installment schedules
- Review due date calculations

---

## Integration Points

### Payment System
- Bills link to payments via document links
- Support for partial and installment payments
- Multi-currency payment handling
- Automatic payment allocation

### Inventory Management
- Inventory bills update stock levels
- Cost updates affect inventory valuation
- Integration with stock movements

### Fixed Assets
- Capital expenditure bills create assets via asset categories
- Automatic asset creation from bill lines
- Integration with asset depreciation system

### Analytic Accounting
- Bills support analytic account assignment
- Cost center and project tracking
- Management reporting integration

---

Vendor bills are essential for accurate expense recognition and accounts payable management. Proper bill processing ensures compliance with accounting standards and provides the foundation for effective cash flow management and vendor relationship maintenance.
