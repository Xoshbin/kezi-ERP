# Receipt and Payment Vouchers: سندات القبض والصرف

This guide explains how to create and manage receipt and payment vouchers in your accounting system. These vouchers record money movements between your company and partners, ensuring proper cash flow tracking and financial control.

---

## What are Receipt and Payment Vouchers?

**Receipt Vouchers (سندات القبض)**: Record money received by your company
- Customer payments for invoices
- Loan proceeds received
- Capital contributions
- Other cash receipts

**Payment Vouchers (سندات الصرف)**: Record money paid by your company  
- Vendor bill payments
- Loan repayments
- Employee advances
- Other cash disbursements

**Accounting Impact**: Each voucher creates journal entries that update your cash/bank accounts and corresponding receivable/payable accounts.

---

## Types of Payment Vouchers

### 1. Settlement Payments
Payments that settle existing invoices or bills:
- **Customer Receipts**: Collecting payment for outstanding invoices
- **Vendor Payments**: Paying outstanding vendor bills

### 2. Partner Advances/Credits
Payments without specific document settlement:
- **Customer Advances**: Prepayments from customers for future orders
- **Vendor Advances**: Prepayments to vendors for future purchases
- **Employee Advances**: Cash advances to employees
- **Loan Transactions**: Borrowing or lending money

---

## Creating Receipt Vouchers (سندات القبض)

Navigate to **Accounting → Payments → Create**

### Step 1: Basic Information

**Payment Type**: Select "Inbound (Receive Money)"
**Journal**: Choose your bank or cash journal
**Payment Date**: Enter the actual receipt date
**Amount**: Enter the total amount received
**Currency**: Select the payment currency
**Payment Method**: Choose method (Bank Transfer, Cash, Check, etc.)
**Reference**: Enter check number, transfer reference, or receipt number

### Step 2: Partner Selection

**Paid by Partner**: Select the customer or partner making the payment

### Step 3: Settlement vs. Advance

#### For Invoice Settlement (Customer Payment)
1. **Link to Invoices**: Click "Add Invoice"
2. **Select Invoices**: Choose outstanding invoices to settle
3. **Allocation**: Specify amount to apply to each invoice
4. **Partial Payments**: You can partially pay invoices

**Example**: Customer pays $5,000 for two invoices
- Invoice #001: $3,000 (full payment)
- Invoice #002: $2,000 (partial payment of $4,500 invoice)

#### For Customer Advance (Prepayment)
1. **No Invoice Links**: Leave invoice section empty
2. **Partner Required**: Ensure customer is selected
3. **Description**: Add note like "Advance payment for future orders"

### Step 4: Review and Confirm

1. **Verify Amounts**: Check total matches actual receipt
2. **Check Allocation**: Ensure invoice allocations are correct
3. **Save as Draft**: Save for review
4. **Confirm**: Click "Confirm" to post the payment

---

## Creating Payment Vouchers (سندات الصرف)

Navigate to **Accounting → Payments → Create**

### Step 1: Basic Information

**Payment Type**: Select "Outbound (Send Money)"
**Journal**: Choose your bank or cash journal
**Payment Date**: Enter the actual payment date
**Amount**: Enter the total amount paid
**Currency**: Select the payment currency
**Payment Method**: Choose method (Bank Transfer, Cash, Check, etc.)
**Reference**: Enter check number, transfer reference, or payment reference

### Step 2: Partner Selection

**Paid to Partner**: Select the vendor or partner receiving payment

### Step 3: Settlement vs. Advance

#### For Bill Settlement (Vendor Payment)
1. **Link to Bills**: Click "Add Vendor Bill"
2. **Select Bills**: Choose outstanding bills to settle
3. **Allocation**: Specify amount to apply to each bill
4. **Partial Payments**: You can partially pay bills

**Example**: Pay vendor $8,000 for three bills
- Bill #VB001: $3,000 (full payment)
- Bill #VB002: $2,500 (full payment)  
- Bill #VB003: $2,500 (partial payment of $4,000 bill)

#### For Vendor Advance (Prepayment)
1. **No Bill Links**: Leave bill section empty
2. **Partner Required**: Ensure vendor is selected
3. **Description**: Add note like "Advance payment for future purchases"

### Step 4: Review and Confirm

1. **Verify Amounts**: Check total matches actual payment
2. **Check Allocation**: Ensure bill allocations are correct
3. **Save as Draft**: Save for review
4. **Confirm**: Click "Confirm" to post the payment

---

## Multi-Currency Payments

### Foreign Currency Receipts/Payments

When receiving or paying in foreign currency:

1. **Payment Currency**: Select the actual currency received/paid
2. **Exchange Rate**: System uses current rate or enter manual rate
3. **Company Currency**: System automatically converts to base currency
4. **Exchange Differences**: Automatically recorded in designated account

**Example**: Receive $1,000 USD when company currency is IQD
- Payment Amount: $1,000 USD
- Exchange Rate: 1 USD = 1,310 IQD
- Company Currency Amount: 1,310,000 IQD

---

## Payment Status Workflow

### Draft Status
- Payment is created but not yet confirmed
- Can be edited or deleted
- No accounting impact yet

### Confirmed Status  
- Payment is finalized and posted
- Journal entries are created
- Cannot be edited (only reversed)

### Reconciled Status
- Payment is matched with bank statement
- Confirms actual bank transaction
- Final status in the workflow

---

## Journal Entry Impact

### Receipt Voucher (Customer Payment)
```
Dr. Bank Account          $5,000
    Cr. Accounts Receivable      $5,000
```

### Payment Voucher (Vendor Payment)
```
Dr. Accounts Payable      $3,000
    Cr. Bank Account             $3,000
```

### Customer Advance Receipt
```
Dr. Bank Account          $2,000
    Cr. Customer Advances        $2,000
```

### Vendor Advance Payment
```
Dr. Vendor Advances       $1,500
    Cr. Bank Account             $1,500
```

---

## Best Practices

### Documentation
- **Supporting Documents**: Attach receipts, invoices, or payment confirmations
- **Clear References**: Use meaningful reference numbers
- **Approval Process**: Implement approval workflow for large amounts

### Timing
- **Same Day Entry**: Record payments on the actual transaction date
- **Bank Reconciliation**: Regular reconciliation ensures accuracy
- **Cutoff Procedures**: Ensure payments are recorded in correct period

### Security
- **User Permissions**: Limit payment creation to authorized users
- **Segregation of Duties**: Separate payment creation from approval
- **Regular Reviews**: Monitor payment patterns for unusual activity

---

## Common Scenarios

### Scenario 1: Customer Pays Multiple Invoices
Customer sends $10,000 to pay three outstanding invoices:

1. Create inbound payment for $10,000
2. Link to Invoice #001 ($4,000)
3. Link to Invoice #002 ($3,500)  
4. Link to Invoice #003 ($2,500)
5. Confirm payment

### Scenario 2: Partial Vendor Bill Payment
Pay $5,000 toward a $8,000 vendor bill:

1. Create outbound payment for $5,000
2. Link to vendor bill
3. Allocate $5,000 (partial amount)
4. Remaining $3,000 stays as outstanding balance
5. Confirm payment

### Scenario 3: Employee Cash Advance
Give employee $500 cash advance:

1. Create outbound payment for $500
2. Select employee as partner
3. No document links (advance payment)
4. Use cash journal
5. Confirm payment

---

## Troubleshooting

### Common Issues

**Problem**: Cannot find invoice/bill to link
**Solution**: 
- Verify invoice/bill is posted (not draft)
- Check partner selection matches
- Ensure invoice/bill has outstanding balance

**Problem**: Payment amount doesn't match invoice total
**Solution**:
- Use partial payment allocation
- Create separate payment for remaining amount
- Check for early payment discounts

**Problem**: Exchange rate seems incorrect
**Solution**:
- Verify exchange rate date
- Check currency rate configuration
- Manually enter correct rate if needed

---

## Reporting and Analysis

### Payment Reports
- **Payment Register**: List all payments by date range
- **Partner Statements**: Show payment history by partner
- **Cash Flow Reports**: Analyze cash receipts and disbursements

### Outstanding Balances
- **Accounts Receivable**: Track unpaid customer invoices
- **Accounts Payable**: Monitor unpaid vendor bills
- **Aging Reports**: Analyze overdue amounts

---

## Integration with Bank Reconciliation

### Bank Statement Matching
1. Import or create bank statements
2. Match confirmed payments to statement lines
3. Identify discrepancies or timing differences
4. Complete reconciliation process

### Outstanding Items
- **Checks in Transit**: Payments confirmed but not yet cleared
- **Deposits in Transit**: Receipts recorded but not yet deposited
- **Bank Charges**: Additional fees not recorded in payments

---

Receipt and payment vouchers are essential for maintaining accurate cash flow records and ensuring proper financial control. Regular creation and reconciliation of these vouchers provides the foundation for reliable financial reporting and cash management.
