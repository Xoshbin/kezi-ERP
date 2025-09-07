# Customer Invoices: Sales Documentation and Revenue Recognition

This guide explains how to create, manage, and process customer invoices in your accounting system. Invoices are formal requests for payment that document sales transactions and trigger revenue recognition in your financial records.

---

## What is a Customer Invoice?

A customer invoice is a commercial document that:
- **Records a Sale**: Documents goods sold or services provided
- **Requests Payment**: Formally asks the customer to pay
- **Recognizes Revenue**: Creates accounting entries for income
- **Tracks Receivables**: Establishes amounts owed by customers

**Accounting Impact**: Posted invoices increase revenue and accounts receivable, following the accrual accounting principle.

---

## Invoice Lifecycle

### 1. Draft Status
- Invoice is being prepared
- Can be edited or deleted
- No accounting impact
- Not visible to customer

### 2. Posted Status
- Invoice is finalized and sent
- Creates journal entries
- Cannot be edited (only credited)
- Available for payment

### 3. Paid Status
- Customer payment received
- Accounts receivable cleared
- Transaction complete

---

## Creating a Customer Invoice

Navigate to **Accounting → Sales → Invoices → Create**

### Step 1: Invoice Header

**Customer**: Select the customer from the partner list
**Invoice Date**: Enter the sale/service date
**Due Date**: Payment deadline (auto-calculated from payment terms)
**Currency**: Select invoice currency (defaults to customer currency)
**Payment Terms**: Choose payment conditions (Net 30, Cash on Delivery, etc.)
**Reference**: Optional customer reference or order number

### Step 2: Invoice Lines

Click **Add Line** to add products or services:

#### Product Lines
**Product**: Select from product catalog
**Description**: Auto-filled from product (editable)
**Quantity**: Number of units sold
**Unit Price**: Price per unit (defaults from product)
**Taxes**: Select applicable tax rates
**Account**: Revenue account (auto-filled from product)

#### Service Lines  
**Service**: Select service item
**Description**: Describe the service provided
**Hours/Units**: Quantity of service
**Rate**: Hourly or unit rate
**Taxes**: Select applicable tax rates
**Account**: Service revenue account

#### Manual Lines
**Description**: Enter custom description
**Quantity**: Enter quantity
**Unit Price**: Enter price
**Account**: Select appropriate revenue account
**Taxes**: Select applicable taxes

### Step 3: Taxes and Totals

**Tax Calculation**: System automatically calculates taxes based on:
- Product/service tax configuration
- Customer tax exemptions
- Fiscal position rules

**Invoice Totals**:
- **Subtotal**: Sum of all line amounts before tax
- **Tax Amount**: Total tax calculated
- **Total Amount**: Final amount due from customer

### Step 4: Additional Information

**Payment Terms**: Defines when payment is due
**Fiscal Position**: Tax rules based on customer location
**Journal**: Sales journal for posting (usually auto-selected)
**Notes**: Additional terms or instructions for customer

---

## Advanced Invoice Features

### Multi-Currency Invoicing

When invoicing in foreign currency:

1. **Invoice Currency**: Select customer's preferred currency
2. **Exchange Rate**: System uses current rate or manual entry
3. **Company Currency**: System converts for accounting records
4. **Exchange Differences**: Handled automatically at payment

**Example**: Invoice customer $1,000 USD when company uses IQD
- Invoice Amount: $1,000 USD
- Exchange Rate: 1 USD = 1,310 IQD  
- Accounting Amount: 1,310,000 IQD

### Payment Terms Integration

**Standard Terms**:
- **Net 30**: Payment due in 30 days
- **2/10 Net 30**: 2% discount if paid within 10 days, otherwise due in 30
- **Cash on Delivery**: Payment due immediately
- **End of Month**: Payment due at month end

**Custom Terms**: Create specific payment schedules for large invoices

### Tax Handling

**Tax-Inclusive Pricing**: Unit prices include tax
**Tax-Exclusive Pricing**: Tax added to unit prices
**Tax Exemptions**: Some customers may be tax-exempt
**Multiple Tax Rates**: Different products may have different tax rates

---

## Invoice Posting Process

### Before Posting - Review Checklist

✅ **Customer Information**: Correct customer selected
✅ **Line Items**: All products/services included with correct quantities
✅ **Pricing**: Unit prices and totals are accurate
✅ **Taxes**: Appropriate tax rates applied
✅ **Payment Terms**: Correct terms selected
✅ **Due Date**: Reasonable payment deadline

### Posting the Invoice

1. **Save as Draft**: Save for review and approval
2. **Review**: Check all details carefully
3. **Post**: Click "Post" to finalize the invoice

**⚠️ Important**: Once posted, invoices cannot be edited. Use credit notes for corrections.

### Journal Entry Created

When posted, the system creates:
```
Dr. Accounts Receivable    $1,100
    Cr. Sales Revenue           $1,000
    Cr. Sales Tax Payable         $100
```

---

## Managing Posted Invoices

### Payment Registration

**From Invoice**: Click "Register Payment" on the invoice
**From Payments**: Create payment and link to invoice
**Automatic Matching**: Bank reconciliation can auto-match payments

### Partial Payments

Customers can make partial payments:
1. Register payment for partial amount
2. Invoice remains "Partially Paid"
3. Outstanding balance tracked automatically
4. Multiple payments can be applied

### Credit Notes

For returns, discounts, or corrections:
1. Click "Credit Note" on original invoice
2. Select items to credit
3. Specify credit amount
4. Post credit note
5. Apply to customer account

---

## Invoice Templates and Customization

### Standard Invoice Layout
- Company logo and information
- Customer billing address
- Invoice number and date
- Line item details
- Tax breakdown
- Payment terms and instructions

### Custom Fields
Add business-specific information:
- Project references
- Purchase order numbers
- Delivery instructions
- Special terms

---

## Recurring Invoices

For subscription or regular services:

### Setup Recurring Invoice
1. Create template invoice
2. Set recurrence pattern (monthly, quarterly, etc.)
3. Define start and end dates
4. Configure automatic posting

### Automatic Processing
- System generates invoices automatically
- Emails sent to customers
- Accounting entries created
- Payment tracking continues normally

---

## Customer Communication

### Invoice Delivery
**Email**: Automatic email with PDF attachment
**Print**: Generate PDF for postal delivery
**Portal**: Customer access through online portal
**API**: Integration with customer systems

### Payment Reminders
**Automatic Reminders**: System sends overdue notices
**Custom Messages**: Personalized reminder content
**Escalation**: Progressive reminder schedule
**Collection Process**: Integration with collection procedures

---

## Reporting and Analysis

### Sales Reports
**Sales by Customer**: Revenue analysis by customer
**Sales by Product**: Product performance tracking
**Sales by Period**: Monthly/quarterly sales trends
**Tax Reports**: VAT/sales tax reporting

### Receivables Management
**Aged Receivables**: Outstanding amounts by age
**Customer Statements**: Account activity summaries
**Collection Reports**: Overdue invoice tracking
**Cash Flow Forecasting**: Expected payment timing

---

## Best Practices

### Invoice Numbering
- **Sequential Numbers**: Use automatic numbering
- **Prefix/Suffix**: Include year or location codes
- **No Gaps**: Maintain continuous sequence for audit purposes

### Approval Workflow
- **Draft Review**: Management approval before posting
- **Credit Limits**: Check customer credit before large invoices
- **Pricing Approval**: Verify special pricing or discounts

### Documentation
- **Supporting Documents**: Attach delivery receipts, contracts
- **Customer Communications**: Keep email records
- **Payment Documentation**: Link payment confirmations

---

## Common Scenarios

### Scenario 1: Standard Product Sale
Sell 10 laptops at $1,000 each with 10% tax:

1. Select customer
2. Add laptop product line (Qty: 10, Price: $1,000)
3. System calculates tax: $1,000
4. Total invoice: $11,000
5. Post invoice

### Scenario 2: Service Invoice with Multiple Rates
Consulting project with different hourly rates:

1. Add senior consultant line (20 hours @ $150/hour)
2. Add junior consultant line (40 hours @ $75/hour)
3. Add project management line (10 hours @ $100/hour)
4. Apply appropriate taxes
5. Post invoice

### Scenario 3: Mixed Currency Transaction
US customer paying in USD while company uses IQD:

1. Select USD as invoice currency
2. Add product lines in USD
3. System converts to IQD for accounting
4. Customer pays in USD
5. Exchange differences handled automatically

---

## Troubleshooting

### Common Issues

**Problem**: Cannot post invoice - validation errors
**Solution**: 
- Check all required fields are completed
- Verify customer has valid address
- Ensure all line items have accounts assigned

**Problem**: Tax calculation seems incorrect
**Solution**:
- Verify customer tax exemption status
- Check product tax configuration
- Review fiscal position settings

**Problem**: Wrong pricing on invoice
**Solution**:
- Check product price list assignments
- Verify customer-specific pricing
- Review discount/promotion rules

---

Customer invoices are the foundation of revenue recognition and accounts receivable management. Proper invoice creation and management ensures accurate financial reporting and efficient cash collection processes.
