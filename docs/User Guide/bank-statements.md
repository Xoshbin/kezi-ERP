# Bank Statements: Cash Flow Documentation and Reconciliation Foundation

This guide explains how to create and manage bank statements in your accounting system. Bank statements are essential for tracking actual cash movements and serve as the foundation for bank reconciliation processes.

---

## What are Bank Statements?

Bank statements are records of all transactions that occurred in your bank accounts during a specific period. They include:
- **Starting Balance**: Account balance at the beginning of the period
- **Transactions**: All deposits, withdrawals, and bank charges
- **Ending Balance**: Account balance at the end of the period

**Purpose**: Bank statements provide the external verification needed to ensure your internal cash records match the bank's records.

---

## Bank Statement Components

### Statement Header
- **Bank Account**: The specific bank account
- **Statement Period**: Start and end dates
- **Starting Balance**: Opening balance from previous statement
- **Ending Balance**: Closing balance for the period
- **Currency**: Account currency
- **Reference**: Statement number or identifier

### Statement Lines (Transactions)
- **Date**: Transaction date
- **Description**: Transaction description from bank
- **Reference**: Check number, transfer ID, or bank reference
- **Amount**: Transaction amount (positive for deposits, negative for withdrawals)
- **Running Balance**: Account balance after each transaction

---

## Creating Bank Statements

Navigate to **Accounting → Banking & Cash → Bank Statements → Create**

### Method 1: Manual Entry

#### Step 1: Statement Header
**Bank Account**: Select the bank account
**Statement Date**: Enter the statement date
**Reference**: Enter statement number (e.g., "STMT-2024-001")
**Starting Balance**: Enter opening balance from previous statement
**Ending Balance**: Enter closing balance from current statement

#### Step 2: Add Statement Lines
Click **Add Line** for each transaction:

**Transaction Date**: Date the transaction occurred
**Description**: Bank's description of the transaction
**Reference**: Check number, wire reference, etc.
**Amount**: 
- **Positive**: For deposits, transfers in, interest earned
- **Negative**: For withdrawals, checks, fees, transfers out

**Partner**: Link to customer/vendor if applicable
**Notes**: Additional information about the transaction

#### Step 3: Validate and Save
1. **Check Balance**: Ensure calculated ending balance matches bank statement
2. **Review Lines**: Verify all transactions are entered correctly
3. **Save**: Save the statement for reconciliation

### Method 2: Import from File

#### Supported Formats
- **CSV**: Comma-separated values
- **Excel**: .xlsx format
- **OFX**: Open Financial Exchange
- **QIF**: Quicken Interchange Format

#### Import Process
1. **Download**: Get statement file from your bank
2. **Upload**: Click "Import" and select file
3. **Map Fields**: Match file columns to system fields
4. **Review**: Check imported transactions
5. **Confirm**: Finalize the import

#### Field Mapping Example
| Bank File Column | System Field |
|------------------|--------------|
| Date | Transaction Date |
| Description | Description |
| Debit | Amount (negative) |
| Credit | Amount (positive) |
| Balance | Running Balance |

---

## Bank Statement Line Types

### Deposits and Credits
**Customer Payments**: Payments received from customers
**Bank Transfers In**: Money transferred from other accounts
**Interest Earned**: Bank interest payments
**Loan Proceeds**: Money received from loans
**Other Income**: Miscellaneous deposits

### Withdrawals and Debits
**Vendor Payments**: Payments made to suppliers
**Bank Transfers Out**: Money transferred to other accounts
**Bank Charges**: Monthly fees, transaction fees
**Loan Payments**: Principal and interest payments
**Other Expenses**: Miscellaneous withdrawals

### Bank-Specific Items
**NSF Fees**: Non-sufficient funds charges
**Overdraft Fees**: Account overdraft charges
**Wire Fees**: Wire transfer charges
**Check Printing**: Cost of check orders
**ATM Fees**: Automated teller machine charges

---

## Multi-Currency Considerations

### Foreign Currency Accounts
When managing foreign currency bank accounts:

1. **Account Currency**: Statement must match account currency
2. **Exchange Rates**: Not applicable at statement level
3. **Conversion**: Handled during reconciliation process

### Foreign Currency Transactions
For transactions in different currencies:

1. **Original Currency**: Record in account's base currency
2. **Exchange Information**: Note original currency in description
3. **Rate Documentation**: Keep exchange rate records

---

## Statement Validation

### Balance Verification
**Calculated Ending Balance** = Starting Balance + Sum of All Transactions
**Must Equal**: Bank's ending balance on statement

### Common Validation Errors
❌ **Balance Mismatch**: Calculated balance ≠ stated ending balance
- **Solution**: Review all transaction amounts and signs

❌ **Missing Transactions**: Statement incomplete
- **Solution**: Add missing deposits or withdrawals

❌ **Duplicate Entries**: Same transaction entered twice
- **Solution**: Remove duplicate lines

❌ **Wrong Dates**: Transactions in wrong period
- **Solution**: Verify transaction dates match bank statement

---

## Bank Statement Management

### Statement Status
**Draft**: Statement being prepared, can be edited
**Confirmed**: Statement finalized, ready for reconciliation
**Reconciled**: All transactions matched and reconciled

### Editing Statements
- **Draft Status**: Full editing capability
- **Confirmed Status**: Limited editing (add notes, correct errors)
- **Reconciled Status**: No editing (create adjustments if needed)

### Statement Corrections
For errors in confirmed statements:
1. **Add Correction Line**: Create offsetting entry
2. **Document Reason**: Explain the correction
3. **Maintain Audit Trail**: Keep record of changes

---

## Integration with Payments

### Automatic Matching
System attempts to match:
- **Payment Amounts**: Exact amount matches
- **Payment Dates**: Within reasonable date range
- **Partner Information**: Customer/vendor matches

### Manual Matching
For unmatched items:
1. **Review Payments**: Check for similar amounts
2. **Date Tolerance**: Consider timing differences
3. **Description Analysis**: Look for identifying information
4. **Partner Clues**: Use customer/vendor names

---

## Reporting and Analysis

### Statement Reports
**Bank Statement Register**: List all statements by account
**Transaction Analysis**: Detailed transaction breakdown
**Balance Trends**: Account balance over time
**Reconciliation Status**: Unreconciled statement tracking

### Cash Flow Analysis
**Cash Receipts**: Analysis of money coming in
**Cash Disbursements**: Analysis of money going out
**Net Cash Flow**: Period-over-period comparison
**Seasonal Patterns**: Identify cash flow trends

---

## Best Practices

### Timely Processing
- **Regular Import**: Process statements weekly or monthly
- **Prompt Reconciliation**: Reconcile within days of receipt
- **Exception Handling**: Address discrepancies immediately

### Data Quality
- **Accurate Entry**: Double-check all amounts and dates
- **Complete Descriptions**: Include meaningful transaction details
- **Consistent Formatting**: Use standard reference formats

### Security
- **Access Control**: Limit statement access to authorized users
- **Audit Trail**: Maintain complete change history
- **Backup Procedures**: Regular backup of statement data

---

## Common Scenarios

### Scenario 1: Monthly Statement Processing
Process monthly bank statement with 50 transactions:

1. Download statement file from bank
2. Import using CSV format
3. Review and map fields correctly
4. Validate ending balance matches
5. Confirm statement for reconciliation

### Scenario 2: Manual Entry for Small Account
Enter petty cash transactions manually:

1. Create new statement for cash account
2. Enter starting balance: $500
3. Add expense lines: office supplies ($25), lunch ($15)
4. Add deposit line: cash advance ($100)
5. Verify ending balance: $560

### Scenario 3: Correction Entry
Correct duplicate transaction entry:

1. Identify duplicate payment entry
2. Add correction line with opposite amount
3. Document reason: "Duplicate entry correction"
4. Verify balance reconciles correctly

---

## Troubleshooting

### Common Issues

**Problem**: Import file format not recognized
**Solution**:
- Check file format (CSV, Excel, etc.)
- Verify file structure matches expected format
- Try manual entry for small statements

**Problem**: Balance doesn't reconcile
**Solution**:
- Recalculate starting balance + transactions
- Check for missing or duplicate entries
- Verify transaction amounts and signs

**Problem**: Cannot match payments to statement lines
**Solution**:
- Check payment dates vs. statement dates
- Look for timing differences (checks clearing later)
- Review payment amounts for exact matches

---

## Integration Points

### Bank Reconciliation
- Statements provide the external data source
- Reconciliation matches internal records to statement
- Discrepancies identified and resolved

### Cash Flow Reporting
- Statement data feeds cash flow reports
- Actual vs. projected cash flow analysis
- Liquidity management and planning

### Audit Requirements
- Statements provide external verification
- Support for financial statement audits
- Compliance with internal controls

---

## Advanced Features

### Automated Processing
**Scheduled Imports**: Automatic daily/weekly imports
**Rule-Based Matching**: Automatic transaction categorization
**Exception Reporting**: Highlight unusual transactions

### Bank Connectivity
**Direct Bank Feeds**: Real-time transaction downloads
**API Integration**: Automated statement retrieval
**Multi-Bank Support**: Manage multiple bank relationships

---

Bank statements are the cornerstone of effective cash management and financial control. Accurate and timely statement processing ensures reliable cash flow reporting and provides the foundation for robust bank reconciliation processes.
