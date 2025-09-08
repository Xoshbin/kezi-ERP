# Bank Statements: Cash Flow Documentation and Reconciliation Foundation

This guide explains how to create and manage bank statements in your accounting system. Bank statements are essential for tracking actual cash movements and serve as the foundation for [bank reconciliation processes](bank-reconciliation.md).

---

## What are Bank Statements?

Bank statements are records of all transactions that occurred in your bank accounts during a specific period. They include:
- **Starting Balance**: Account balance at the beginning of the period
- **Transactions**: All deposits, withdrawals, and bank charges
- **Ending Balance**: Account balance at the end of the period

**Purpose**: Bank statements provide the external verification needed to ensure your internal cash records match the bank's records through the [reconciliation process](bank-reconciliation.md).

---

## Bank Statement Components

### Statement Header
- **Bank Journal**: The specific bank journal (must be of type "Bank")
- **Currency**: Statement currency (defaults to company currency)
- **Reference**: Statement number or identifier
- **Date**: Statement date
- **Starting Balance**: Opening balance from previous statement
- **Ending Balance**: Closing balance for the period

### Statement Lines (Transactions)
- **Date**: Transaction date
- **Description**: Transaction description from bank
- **Partner**: Customer/vendor if applicable
- **Amount**: Transaction amount (positive for deposits, negative for withdrawals)
- **Foreign Currency**: Optional - if transaction was in different currency
- **Amount in Foreign Currency**: Original amount before conversion

---

## Creating Bank Statements

Navigate to **Accounting → Banking & Cash → Bank Statements → Create**

### Manual Entry Process

#### Step 1: Statement Header Information
**Currency**: Select the statement currency (defaults to company currency)
**Bank Journal**: Select the bank journal (must be of type "Bank")  
**Reference**: Enter statement number (e.g., "STMT-2024-001")
**Date**: Enter the statement date
**Starting Balance**: Enter opening balance from previous statement
**Ending Balance**: Enter closing balance from current statement

#### Step 2: Add Statement Lines
Use the statement lines table to add each transaction:

**Date**: Transaction date from bank statement
**Description**: Bank's description of the transaction
**Partner**: Link to customer/vendor if applicable (optional)
**Amount**: 
- **Positive values**: For deposits, transfers in, interest earned
- **Negative values**: For withdrawals, checks, fees, transfers out

**Foreign Currency Fields** (optional for multi-currency transactions):
- **Foreign Currency**: Select the original transaction currency
- **Amount in Foreign Currency**: Enter the original amount before conversion

#### Step 3: Validation and Save
1. **Balance Validation**: System automatically calculates ending balance
2. **Review Lines**: Verify all transactions are entered correctly
3. **Save**: Create the statement and prepare it for [reconciliation](bank-reconciliation.md)

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

### Foreign Currency Transactions
The system supports transactions originally made in different currencies:

1. **Statement Currency**: All amounts are stored in the statement's base currency
2. **Original Currency**: Track the original transaction currency and amount
3. **Exchange Rates**: Conversion is handled when recording the transaction
4. **Display**: Both original and converted amounts are visible

### Multi-Currency Bank Accounts
When managing foreign currency bank accounts:
- Each statement must match the journal's currency
- Conversion rates are applied during transaction entry
- [Reconciliation](bank-reconciliation.md) handles currency differences automatically

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

### Statement Access
- **Create**: Available to authorized accounting users
- **Edit**: Modify unreconciled statements only
- **View**: All users can view statements based on permissions
- **Reconcile**: Available when reconciliation is enabled for the company

### Statement Status
Statements have an implicit status based on their reconciliation state:
- **Draft**: Recently created, can be edited freely
- **Ready**: Complete statement, ready for [reconciliation](bank-reconciliation.md)
- **Partially Reconciled**: Some lines have been reconciled
- **Fully Reconciled**: All lines processed through reconciliation

### Editing Statements
- **Before Reconciliation**: Full editing capability
- **During Reconciliation**: Limited editing recommended
- **After Reconciliation**: Create new correcting entries instead of editing

---

## Integration with Payments

### Automatic Relationship Tracking
The system tracks relationships between:
- **Bank Statement Lines**: External bank transactions
- **Payment Records**: Internal payment entries
- **Reconciliation Status**: Whether items have been matched

### Payment Linking
During [bank reconciliation](bank-reconciliation.md):
1. **Unreconciled Payments**: System shows confirmed payments without bank statement links
2. **Matching Process**: Users match payments to statement lines
3. **Status Updates**: Matched payments change status to "Reconciled"
4. **Foreign Key Relationship**: Direct link between payment and statement line records

### Relation Manager View
From payment records, users can view:
- Associated bank statement lines
- Reconciliation status and dates
- Statement references and amounts

---

## Reporting and Analysis

### Statement Management Views
**Bank Statement List**: Overview of all statements with key information
- Reference, Bank Journal, Date, Currency
- Starting and Ending Balances
- Creation and modification dates

**Statement Details**: Individual statement view showing:
- Complete header information
- All transaction lines with amounts and descriptions
- Reconciliation status indicators

### Integration Reports
**Cash Flow Analysis**: Statement data contributes to:
- Cash position reporting
- Liquidity analysis
- Bank balance verification
- Reconciliation status tracking

---

## Best Practices

### Data Entry
- **Accurate Information**: Double-check all amounts and dates
- **Complete Descriptions**: Include meaningful transaction details
- **Partner Linking**: Associate transactions with customers/vendors when possible
- **Foreign Currency**: Record original amounts for better tracking

### Workflow Management
- **Regular Entry**: Create statements promptly after receiving bank statements
- **Systematic Review**: Verify calculated balances before saving
- **Prompt Reconciliation**: Begin [reconciliation process](bank-reconciliation.md) immediately after creation

### Access Control
- **User Permissions**: Limit statement creation to authorized accounting staff
- **Review Process**: Implement approval workflows for significant transactions
- **Audit Trail**: Maintain complete change history

---

## Common Scenarios

### Scenario 1: Monthly Statement Creation
Create a monthly bank statement with 25 transactions:

1. Navigate to Bank Statements → Create
2. Select currency and bank journal
3. Enter statement reference and date
4. Input starting and ending balances
5. Add transaction lines using the table interface
6. Verify balance calculation
7. Save statement and proceed to [reconciliation](bank-reconciliation.md)

### Scenario 2: Multi-Currency Transaction Entry
Record a foreign currency transaction:

1. Create statement in base currency (e.g., IQD)
2. Add transaction line with converted amount
3. Select original currency (e.g., USD)
4. Enter original amount in foreign currency
5. System maintains both amounts for tracking

### Scenario 3: Error Correction
Correct an incorrect transaction amount:

1. Edit the statement (if not yet reconciled)
2. Modify the incorrect transaction line
3. Verify ending balance is still correct
4. Save changes
5. If already reconciled, create a new correcting statement instead

---

## Troubleshooting

### Common Issues

**Problem**: Cannot select bank journal
**Solution**:
- Ensure the journal type is set to "Bank"
- Verify journal belongs to the current company
- Check user permissions for journal access

**Problem**: Balance calculation doesn't match
**Solution**:
- Review all transaction amounts and signs
- Check for missing or duplicate entries
- Verify starting balance is correct
- Ensure foreign currency conversions are accurate

**Problem**: Cannot edit statement
**Solution**:
- Check if statement lines have been reconciled
- Verify user has edit permissions
- Consider creating a new correcting statement instead

---

## Next Steps

Once your bank statement is created and validated:

1. **Proceed to Reconciliation**: Use the [Bank Reconciliation](bank-reconciliation.md) feature to match statement lines with system payments
2. **Monitor Status**: Track reconciliation progress through the statement views
3. **Generate Reports**: Use statement data for cash flow analysis and financial reporting

---

Bank statements form the foundation of accurate cash management and provide the external verification needed for reliable [bank reconciliation](bank-reconciliation.md) processes.
