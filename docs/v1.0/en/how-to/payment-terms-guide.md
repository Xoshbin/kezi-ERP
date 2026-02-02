---
title: Payment Terms
icon: heroicon-o-calendar-days
order: 8
---


This comprehensive guide explains how payment terms work in your accounting system, covering setup, configuration, automatic due date calculations, and installment generation. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What are Payment Terms?

Payment terms are standardized payment conditions that define when and how invoices and bills should be paid, automatically calculating due dates and generating payment installments when applied to documents.

- **Due Date Calculation**: Automatically calculate when payments are due based on predefined rules
- **Installment Generation**: Create multiple payment installments for complex payment arrangements
- **Early Payment Discounts**: Support for discount terms encouraging prompt payment
- **Standardized Conditions**: Ensure consistent payment expectations across all transactions

**Accounting Purpose**: Payment terms provide structured payment expectations that support cash flow management and accounts receivable/payable aging analysis.

---

## System Requirements

### Company Configuration
- **Payment Terms Setup**: Payment terms must be configured for company use
- **Currency Support**: Terms must be compatible with invoice and bill currencies
- **Date Calculations**: System must support various due date calculation methods
- **Discount Processing**: Early payment discount processing must be enabled if using discount terms

### Prerequisites
1. **Company Setup**: Company must be configured with appropriate payment processing settings
2. **Invoice/Bill Configuration**: Customer invoices and vendor bills must support payment term assignment
3. **Accounting Integration**: Payment terms must integrate with accounts receivable and payable processes
4. **User Permissions**: Access to settings and payment term configuration

---

## Where to find it in the UI

Navigate to **Settings → Payment Terms**

Payment terms also appear in:
- **Customer Invoices**: Payment term selection during invoice creation
- **Vendor Bills**: Payment term assignment for bill processing
- **Customer Records**: Default payment terms for specific customers
- **Vendor Records**: Default payment terms for specific vendors

**Tip**: The header's Help/Docs button opens this guide.

---
4. The system will use this term to calculate installments when the invoice is posted

### In Vendor Bills

1. Go to **Accounting → Vendor Bills → Create Vendor Bill**
2. Fill in the basic bill details
3. Select a **Payment Term** from the dropdown
4. The system will use this term to calculate installments when the bill is posted

### Viewing Payment Terms

Payment terms are displayed in the list views:
- **Invoice List**: Shows the payment term name in a dedicated column
- **Vendor Bill List**: Shows both payment term and calculated due date

## How Payment Terms Work

### 1. Document Creation
- User selects a payment term when creating an invoice or vendor bill
- Payment term is stored with the document

### 2. Document Posting
- When the document is posted, the system reads the payment term
- Calculates installments based on the payment term configuration
- Creates payment installment records automatically

### 3. Payment Processing
- When payments are registered, they are applied to installments
- Installment statuses update automatically:
  - **Pending** → **Partially Paid** → **Paid**
  - **Cancelled** (if document is cancelled)

### 4. Payment Tracking
- Users can track payment progress through existing payment interfaces
- Overdue installments are automatically identified
- Payment aging reports use installment data

## Payment Term Types

### 1. Immediate Payment
- Payment due immediately upon receipt
- Creates one installment with due date = document date

### 2. Net Terms
- Payment due within specified number of days
- Example: Net 30 = payment due in 30 days
- Creates one installment with calculated due date

### 3. Early Payment Discounts
- Discount available if paid within discount period
- Example: 2% 10, Net 30 = 2% discount if paid within 10 days, otherwise net 30
- Creates one installment with discount information

### 4. End of Month
- Payment due at the end of the month (+ optional additional days)
- Example: EOM + 15 = payment due 15 days after month end
- Handles month-end calculations automatically

### 5. Day of Month
- Payment due on a specific day of the following month
- Example: 15th of Next Month = payment due on 15th of next month
- Handles month transitions and leap years

## Best Practices

### 1. Standardization
- Use consistent payment terms across similar customers/vendors
- Set default payment terms on partner records
- Review and update terms regularly

### 2. Cash Flow Management
- Use shorter terms for better cash flow
- Offer early payment discounts to encourage faster payment
- Consider installment terms for large amounts

### 3. Customer Relations
- Clearly communicate payment terms to customers
- Be consistent in applying terms
- Consider customer payment history when setting terms

### 4. Vendor Management
- Negotiate favorable payment terms with vendors
- Take advantage of early payment discounts when possible
- Monitor vendor payment terms for cash flow planning

## Technical Details

### Database Structure
- `payment_terms` - Main payment term definitions
- `payment_term_lines` - Individual installment configurations
- `payment_installments` - Generated installments for documents
- `payment_installment_links` - Links between payments and installments

### Automatic Calculations
- Due dates calculated based on document date and term configuration
- Installment amounts calculated with proper rounding
- Exchange rates captured for multi-currency scenarios
- Discount deadlines calculated automatically

### Integration Points
- **Invoice/Bill Creation** - Payment term selection
- **Document Posting** - Installment generation
- **Payment Registration** - Installment status updates
- **Reporting** - Payment aging and cash flow analysis

## Troubleshooting

### Payment Terms Not Showing
- Ensure payment terms are active (`is_active = true`)
- Check that payment terms exist for the current company
- Run `php artisan db:seed --class=PaymentTermsSeeder` to create default terms

### Installments Not Generated
- Verify the document has a payment term assigned
- Check that the document has been posted (not draft)
- Ensure payment term lines are properly configured

### Incorrect Due Dates
- Review payment term line configuration
- Check document date is correct
- Verify payment term type and calculation method

## Support

For additional help with the payment terms system:
1. Check the Settings → Payment Terms interface
2. Review existing payment term configurations
3. Test with sample documents in a development environment
4. Consult the technical documentation for advanced configurations

## Related Documentation

- [Customer Invoices Guide](customer-invoices.md) - Learn more about creating invoices
- [Vendor Bills Guide](vendor-bills.md) - Learn more about vendor bill management
- [Payments Guide](payments.md) - Learn more about payment processing
