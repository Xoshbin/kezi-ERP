---
title: Customer Invoices
icon: heroicon-o-document-text
order: 3
---


This comprehensive guide explains how customer invoices work in your accounting system, covering creation, payment processing, multi-currency support, and revenue recognition. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is a Customer Invoice?

A customer invoice is a commercial document that formally requests payment for goods sold or services provided, creating legal and accounting records for the transaction.

- **Records a Sale**: Documents goods sold or services provided to customers
- **Requests Payment**: Formally asks the customer to pay for delivered goods/services
- **Recognizes Revenue**: Creates accounting entries for income under accrual accounting
- **Tracks Receivables**: Establishes amounts owed by customers in accounts receivable

**Accounting Impact**: Posted invoices increase revenue and accounts receivable, following the accrual accounting principle that recognizes income when earned, not when cash is received.

---

## System Requirements


### Prerequisites
1. **Customer Record**: Customer must exist in the system with complete information
2. **Product/Service Setup**: Items to be invoiced must be configured in the catalog
3. **Tax Configuration**: Tax rates and rules must be set up for applicable jurisdictions
4. **Payment Terms**: Payment terms must be defined for customer agreements
5. **Currency Configuration**: Invoice currency must be active and configured
6. **User Permissions**: Appropriate access to sales and accounting features

---

## Where to find it in the UI

Navigate to **Accounting → Sales → Invoices**

Customer invoices also appear in:
- **Create Action**: "Create" button for new invoice entry
- **Customer Records**: Related invoices section showing invoice history
- **Dashboard**: Recent invoices and sales summaries

**Tip**: The header's Help/Docs button opens this guide.

---

## Invoice Status Workflow

### Draft Status
- Invoice is being prepared and can be freely edited
- No accounting impact created yet
- No permanent invoice number assigned
- Not visible to customer
- Can be deleted if no longer needed

### Posted Status
- Invoice is finalized with permanent invoice number
- Journal entries created for accounting recognition
- Cannot be edited (use adjustment documents for corrections)
- Available for customer payment
- Stock moves generated for storable products
- Payment installments created based on payment terms

### Paid Status
- Customer payment received and applied to invoice
- Payment state shows as "Paid" 
- Accounts receivable balance cleared
- Transaction cycle complete

### Cancelled Status
- Invoice has been cancelled before or after posting
- Reversing journal entry created if previously posted
- Cannot receive payments
- Complete audit trail maintained

---

## Creating a Customer Invoice

Navigate to **Accounting → Sales → Invoices → Create**

### Step 1: Customer & Currency Information

**Customer**: Select the customer from the partner list
- Create new customers directly from the invoice form if needed
- Customer information includes name, email, contact person, and address

**Currency**: Select invoice currency 
- Defaults to customer's preferred currency or company base currency
- Multi-currency support with automatic exchange rate conversion
- Exchange rates captured at invoice posting time

**Current Exchange Rate**: Displayed for foreign currencies
- Shows the current exchange rate for reference
- Will be locked when invoice is posted

### Step 2: Invoice Details

**Fiscal Position**: Optional tax rules based on customer location/type
**Invoice Date**: Date of sale or service delivery (required)
- Must not be in a locked period
**Due Date**: Payment deadline (required)
**Payment Terms**: Choose payment conditions (see [Payment Terms Guide](payment-terms-guide.md))
- Terms determine payment installment schedule
- Affects due date calculation

### Step 3: Invoice Lines

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

### Step 4: Taxes and Totals

**Tax Calculation**: System automatically calculates taxes based on:
- Product/service tax configuration
- Customer tax exemption status
- Fiscal position rules (if applicable)

**Invoice Totals**:
- **Subtotal**: Sum of all line amounts before tax
- **Tax Amount**: Total tax calculated
- **Total Amount**: Final amount due from customer

**Multi-Currency Display**: For foreign currency invoices:
- Invoice amounts shown in invoice currency
- Company currency equivalents calculated automatically
- Exchange rate locked at posting time

### Step 5: Review and Confirmation

Before posting, review:
- Customer details and currency
- All line items with correct quantities and prices
- Tax calculations
- Payment terms and due date

---

## Advanced Invoice Features

### Multi-Currency Invoicing

When invoicing in foreign currency:

1. **Invoice Currency**: Select customer's preferred currency
2. **Exchange Rate**: System captures current rate automatically
3. **Company Currency**: System converts for accounting records
4. **Exchange Rate Lock**: Rate is locked when invoice is posted
5. **Payment Handling**: Exchange differences handled at payment time

**Example**: Invoice customer $1,000 USD when company uses IQD
- Invoice Amount: $1,000 USD
- Exchange Rate: 1 USD = 1,310 IQD (locked at posting)
- Accounting Amount: 1,310,000 IQD
- Company reports show both currencies

### Payment Terms Integration

Payment terms automatically generate payment installments when invoice is posted.

**Standard Terms Available**:
- **Immediate**: Payment due immediately
- **Net Terms**: Net 15, Net 30, Net 45, Net 60
- **Early Payment Discounts**: 2% 10 Net 30, 1% 15 Net 30
- **End of Month Terms**: EOM, EOM + 15, EOM + 30
- **Installment Terms**: Multiple payment schedules

For details, see [Payment Terms Guide](payment-terms-guide.md).

### Payment State Tracking

The system tracks payment state separately from invoice status:
- **Unpaid**: No payments received
- **Partially Paid**: Some payments received
- **Paid**: Fully paid
- **Overpaid**: Payments exceed invoice amount

### Fiscal Position Handling

Fiscal positions automatically adjust:
- **Tax Rates**: Based on customer location
- **Tax Exemptions**: For qualified customers
- **Account Mapping**: Revenue account adjustments
- **Compliance**: Local tax regulations

---

## Invoice Posting Process

### Before Posting - Review Checklist

✅ **Customer Information**: Correct customer selected with valid details
✅ **Currency & Exchange Rate**: Appropriate currency selected
✅ **Line Items**: All products/services included with correct quantities
✅ **Pricing**: Unit prices and totals are accurate
✅ **Taxes**: Appropriate tax rates applied per fiscal position
✅ **Payment Terms**: Correct terms selected
✅ **Due Date**: Reasonable payment deadline
✅ **Accounts**: Revenue accounts properly assigned

### Posting the Invoice

1. **Save as Draft**: Save for review and approval
2. **Review**: Check all details carefully using checklist above
3. **Confirm**: Click "Confirm" to finalize the invoice

**⚠️ Important**: Once posted, invoices cannot be edited. Use adjustment documents for corrections.

### What Happens When Posted

The system automatically:

1. **Assigns Invoice Number**: Permanent sequential number generated
2. **Creates Journal Entry**: Accounting entries for revenue recognition
3. **Locks Exchange Rate**: Foreign currency rate fixed for consistency
4. **Generates Payment Installments**: Based on selected payment terms
5. **Creates Stock Moves**: For storable products (inventory reduction)
6. **Converts Currency**: Amounts converted to company currency
7. **Updates Payment State**: Sets initial payment state as "Unpaid"

### Journal Entry Created

For a standard invoice, the system creates:
```
Dr. Accounts Receivable (Customer)     $1,100
    Cr. Sales Revenue                       $1,000
    Cr. Sales Tax Payable                     $100
```

For multi-currency invoices, amounts are converted to company currency using the locked exchange rate.

---

## Managing Posted Invoices

### Payment Registration

**From Invoice**: Click "Register Payment" directly on the invoice
- Select payment journal (bank/cash account)
- Enter payment date and amount
- Add optional reference
- System creates and confirms payment automatically

**From Payments Module**: Create payment separately and link to invoices
**Bank Reconciliation**: Payments can be matched automatically during reconciliation

For detailed payment information, see [Payments Guide](payments.md).

### Payment Installments

Posted invoices automatically generate payment installments based on payment terms:
- **Single Payment**: For simple terms like Net 30
- **Multiple Installments**: For complex payment schedules
- **Payment Tracking**: Each installment tracked separately
- **Aging Reports**: Based on installment due dates

### Partial Payments

The system fully supports partial payments:
1. Register payment for partial amount
2. Payment state changes to "Partially Paid"
3. Remaining balance tracked automatically
4. Multiple payments can be applied
5. Payment installments updated accordingly

### Adjustment Documents

For corrections after posting, use adjustment documents instead of editing:

**Credit Notes**: For returns, discounts, or corrections
1. Navigate to the invoice
2. Use "Adjustment Documents" relation manager
3. Create credit note for specific amounts
4. Credit automatically applies to customer account

**Debit Notes**: For additional charges
- Similar process but increases customer balance
- Less common but available when needed

### PDF Generation and Delivery

**View PDF**: Click "View PDF" to preview invoice
**Download PDF**: Click "Download PDF" for file download
**Templates**: Multiple PDF templates available per company
**Email Delivery**: PDFs can be emailed to customers (setup required)

### Invoice Cancellation

For posted invoices that need to be cancelled:
- Cancellation creates reversing journal entry
- Invoice status changes to "Cancelled"
- Audit trail maintained for compliance
- Cannot be undone once cancelled

---

## Invoice Templates and Display

### Standard Invoice Information
- Company logo and complete business information
- Customer billing address and contact details
- Invoice number (auto-generated when posted)
- Invoice date and due date
- Currency and exchange rate (for foreign currencies)
- Line item details with quantities, prices, and taxes
- Tax breakdown by rate
- Payment terms and instructions
- Multi-currency totals when applicable

### PDF Templates
Multiple PDF templates available per company:
- **Classic Template**: Standard business format
- **Modern Template**: Contemporary design
- **Custom Templates**: Company-specific designs
- Template selection available in company settings

### Multi-Currency Display
For foreign currency invoices:
- Primary amounts in invoice currency
- Company currency equivalents shown
- Exchange rate clearly displayed
- Both currencies in accounting reports

---

## Recurring Invoices

*Note: Recurring invoice functionality is planned for future releases. Currently, invoices must be created manually.*

---

## Customer Communication

### Invoice Delivery
**PDF Generation**: View and download professional PDF invoices
**Email Delivery**: Can be configured for automatic customer delivery
**Multiple Templates**: Choose from available PDF templates
**Multi-Language**: PDFs respect company and customer language settings

### Payment Follow-up
**Payment State Tracking**: Real-time payment status updates
**Installment Monitoring**: Track individual payment installments
**Overdue Tracking**: Automatic aging of overdue amounts
**Customer Statements**: Generate account statements showing all activity

*Note: Automatic payment reminder functionality is planned for future releases.*

---

## Reporting and Analysis

### Sales Reports
**Sales by Customer**: Revenue analysis by customer with multi-currency support
**Sales by Product**: Product performance tracking across all currencies
**Sales by Period**: Monthly/quarterly sales trends with currency conversion
**Tax Reports**: VAT/sales tax reporting with proper currency handling
**Multi-Currency Reports**: Consolidated reports in base currency

### Receivables Management
**Aged Receivables**: Outstanding amounts by age with currency conversion
**Payment Installments Report**: Upcoming payment schedules
**Customer Statements**: Account activity summaries in customer currency
**Collection Reports**: Overdue invoice tracking with payment state
**Cash Flow Forecasting**: Expected payment timing based on installments

### Multi-Company Reports
For businesses with multiple companies:
- Consolidated sales reports
- Inter-company transaction analysis
- Currency exposure reports
- Cross-company customer analysis

---

## Best Practices

### Invoice Numbering
- **Sequential Numbers**: Automatic numbering system ensures no gaps
- **Company-Specific**: Each company has its own numbering sequence
- **Audit Compliance**: Sequential numbering required for tax compliance
- **Draft References**: Draft invoices show "DRAFT-00001" format until posted

### Multi-Currency Management
- **Consistent Currency**: Use customer's preferred currency when possible
- **Exchange Rate Monitoring**: Review rates before posting large invoices
- **Currency Risk**: Consider hedging for significant foreign currency exposure
- **Reporting Currency**: Maintain reports in both invoice and company currencies

### Approval Workflow
- **Draft Review**: Management approval before posting recommended
- **Credit Limits**: Monitor customer credit before large invoices
- **Pricing Approval**: Verify special pricing or discounts
- **Tax Compliance**: Ensure proper fiscal position assignment

### Documentation and Audit Trail
- **Supporting Documents**: Link delivery receipts and contracts
- **Change History**: System maintains complete audit log
- **Payment Documentation**: Link payment confirmations and bank statements
- **Multi-Currency Records**: Maintain exchange rate documentation

---

## Common Scenarios

### Scenario 1: Standard Product Sale (Single Currency)
Sell 10 laptops at $1,000 each with 10% tax in USD:

1. Select customer and USD currency
2. Add laptop product line (Qty: 10, Price: $1,000)
3. System calculates tax: $1,000
4. Total invoice: $11,000 USD
5. Confirm invoice to generate invoice number and journal entries

### Scenario 2: Multi-Currency Service Invoice
US customer paying in USD while company uses IQD:

1. Select customer and USD as invoice currency
2. Add consulting service lines in USD
3. System shows current exchange rate (e.g., 1 USD = 1,310 IQD)
4. Confirm invoice - exchange rate locked at 1,310
5. Accounting entries created in IQD (company currency)
6. Customer pays in USD - exchange differences handled automatically

### Scenario 3: Complex Payment Terms Invoice
Large project with 30-60-90 day payment terms:

1. Create invoice with total amount $30,000
2. Select "30-60-90 Days" payment terms
3. Confirm invoice
4. System generates 3 payment installments:
   - $10,000 due in 30 days
   - $10,000 due in 60 days  
   - $10,000 due in 90 days
5. Track each installment separately in payment reports

### Scenario 4: Invoice with Early Payment Discount
Encourage early payment with "2% 10 Net 30" terms:

1. Create standard invoice
2. Select "2% 10 Net 30" payment terms
3. Confirm invoice
4. Customer pays within 10 days - apply 2% discount via adjustment document
5. System tracks discount as sales discount account

### Scenario 5: Invoice Correction via Adjustment Document
Correct pricing error on posted invoice:

1. Original invoice posted for $5,000
2. Discover price should be $4,500
3. Create credit note adjustment document for $500
4. Apply credit to customer account
5. Original invoice remains unchanged for audit trail

---

## Troubleshooting

### Common Issues

**Problem**: Cannot confirm invoice - validation errors
**Solution**: 
- Verify customer has complete address information
- Ensure all invoice lines have revenue accounts assigned
- Check that invoice date is not in a locked accounting period
- Confirm currency and exchange rate are available

**Problem**: Tax calculation seems incorrect
**Solution**:
- Verify customer's fiscal position assignment
- Check product tax configuration in master data
- Review customer tax exemption status
- Ensure fiscal position tax mapping is correct

**Problem**: Exchange rate not updating
**Solution**:
- Check currency rate configuration
- Verify rate date ranges
- Ensure currency service is properly configured
- Contact system administrator for rate source settings

**Problem**: Payment installments not generating
**Solution**:
- Verify payment terms are properly configured
- Check payment term lines setup
- Ensure payment terms are active
- Review payment term calculation rules

**Problem**: PDF generation fails
**Solution**:
- Check company logo and template configuration
- Verify PDF service is running
- Ensure customer address information is complete
- Contact system administrator if templates are missing

### Integration Issues

**Problem**: Journal entries not created
**Solution**:
- Verify chart of accounts configuration
- Check revenue account mapping for products
- Ensure journal configuration is complete
- Review accounting period setup

**Problem**: Stock moves not generated
**Solution**:
- Verify products are marked as "Storable"
- Check warehouse and location configuration
- Ensure inventory management is enabled
- Review product stock settings

---

## Related Documentation

- [Payment Terms Guide](payment-terms-guide.md) - Detailed payment terms configuration
- [Payments Guide](payments.md) - Payment processing and management
- [Bank Reconciliation Guide](bank-reconciliation.md) - Matching payments to bank statements
- [Vendor Bills Guide](vendor-bills.md) - Managing supplier invoices
- [Stock Management Guide](stock-management-user-guide.md) - Inventory and stock moves

---

Customer invoices are the foundation of revenue recognition and accounts receivable management. The multi-currency support, payment installments, and adjustment document features provide comprehensive invoice management for businesses of all sizes operating in local or international markets.
