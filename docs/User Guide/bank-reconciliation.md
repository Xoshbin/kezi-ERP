# Bank Reconciliation: Ensuring Cash Accuracy and Financial Control

This guide explains how to perform bank reconciliation in your accounting system. Bank reconciliation is the process of matching your internal cash records with the bank's records to ensure accuracy and identify discrepancies. This feature requires [bank statements](bank-statements.md) to be created first.

**Important**: Bank reconciliation is only available when the "Enable Reconciliation" setting is activated in your company configuration.

---

## What is Bank Reconciliation?

Bank reconciliation is the process of comparing your company's cash records with the [bank statement](bank-statements.md) to:
- **Verify Accuracy**: Ensure internal records match bank records
- **Identify Discrepancies**: Find mistakes or timing differences
- **Update Records**: Mark payments as reconciled
- **Handle Unmatched Items**: Write off small discrepancies or investigate larger ones

**Accounting Principle**: Your payment records should match the bank statement transactions after accounting for timing differences.

---

## System Requirements

### Company Settings
- **Enable Reconciliation**: Must be turned on in company settings
- **Default Accounts**: Bank accounts and reconciliation accounts must be configured
- **User Permissions**: Users need appropriate access to reconciliation features

### Prerequisites
1. **Bank Statement Created**: [Bank statement](bank-statements.md) must exist with transaction lines
2. **Payment Records**: System payments must be confirmed and ready for matching
3. **Currency Alignment**: Both bank statements and payments should use compatible currencies

---

## Reconciliation Process Overview

### 1. Access Control
- Check that reconciliation is enabled for your company
- Verify you have permission to perform reconciliation
- Ensure bank statement is available for processing

### 2. Interface Setup
- Select bank statement to reconcile
- System loads interactive reconciliation interface
- Three-panel view displays bank transactions, system payments, and summary

### 3. Matching Phase
- Select bank statement lines from left panel
- Select corresponding system payments from right panel
- Real-time balance calculation shows matching status

### 4. Resolution Phase
- Reconcile perfectly matched items
- Write off small unmatched amounts
- Investigate significant discrepancies

---

## Starting Bank Reconciliation

Navigate to **Accounting → Banking & Cash → Bank Statements**

### Accessing Reconciliation
1. **Find Bank Statement**: Locate the statement you want to reconcile
2. **Click Reconcile Action**: Use the "Reconcile" button (green scale icon)
3. **Access Control**: Action only appears when reconciliation is enabled

**Note**: The reconcile action is only visible when your company has reconciliation enabled in settings.

### Reconciliation Interface Layout
The reconciliation screen uses an interactive Livewire interface with three main sections:

**Left Panel - Bank Transactions**: Shows unreconciled bank statement lines
**Right Panel - System Payments**: Displays confirmed payments without reconciliation
**Bottom Panel - Reconciliation Summary**: Real-time totals and balance validation

---

## Matching Transactions

### Interactive Selection Process
The system provides real-time selection and calculation:

#### Bank Transaction Selection
1. **View Available Lines**: Left panel shows unreconciled bank statement lines
2. **Select Items**: Click checkboxes to select bank transactions
3. **Real-time Total**: System calculates total of selected bank items
4. **Currency Display**: All amounts shown in statement currency

#### System Payment Selection  
1. **View Available Payments**: Right panel shows confirmed, unreconciled payments
2. **Select Items**: Click checkboxes to select system payments
3. **Multi-Currency Support**: Payments in different currencies are converted automatically
4. **Real-time Total**: System calculates total of selected payments

### Balance Validation
The reconciliation summary provides instant feedback:
- **Bank Total**: Sum of selected bank statement lines
- **System Total**: Sum of selected system payments (with currency conversion)
- **Difference**: Calculated variance between totals
- **Balance Status**: Green "Balanced" when difference is zero

### Reconciliation Execution
When totals are balanced:
1. **Click "Reconcile Selected"**: Process the matching
2. **System Updates**: Payment status changes to "Reconciled"  
3. **Link Creation**: Bank statement lines linked to payment records
4. **Automatic Cleanup**: Selections cleared for next set of transactions

---

## Handling Unmatched Items

### Write-Off Functionality
For small discrepancies or bank fees that don't have corresponding system payments:

#### Bank Statement Line Write-Offs
1. **Identify Unmatched Line**: Find bank transaction without corresponding payment
2. **Click Write-Off Action**: Use the "X" icon in the bank transactions table
3. **Select Expense Account**: Choose appropriate expense account (e.g., "Bank Charges")
4. **Enter Reason**: Provide explanation for the write-off
5. **Create Entry**: System generates journal entry and marks line as reconciled

#### Write-Off Examples
- **Bank Fees**: Monthly maintenance fees, transaction fees
- **Interest Charges**: Overdraft fees, loan interest
- **Small Discrepancies**: Rounding differences, minor calculation variances

### Investigation Required
For larger discrepancies:
1. **Review Transaction Details**: Check dates, amounts, and descriptions
2. **Verify System Payments**: Ensure all payments are properly recorded
3. **Check Timing**: Look for payments that might clear in next period
4. **Create Missing Entries**: Record overlooked transactions if necessary

---

## Multi-Currency Reconciliation

### Automatic Currency Conversion
The system handles multi-currency scenarios automatically:

#### Different Currency Payments
- **Automatic Conversion**: Payments in different currencies are converted to statement currency
- **Real-time Rates**: Uses current exchange rates for calculations
- **Display Information**: Shows both original and converted amounts
- **Balance Calculation**: All totals calculated in statement currency

#### Currency Indicators
- **Same Currency**: Green indicator for matching currencies
- **Different Currency**: Yellow indicator showing conversion applied
- **Conversion Details**: Hover or click for rate information

### Reconciliation Considerations
- **Rate Fluctuations**: Small differences may occur due to exchange rate changes
- **Tolerance Settings**: System may allow minor variances for currency conversions
- **Documentation**: Conversion details are recorded for audit purposes

---

## Completing Reconciliation

### Reconciliation Workflow
The interactive interface provides continuous feedback:

#### Real-Time Status Monitoring
- **Balance Indicator**: Shows "Balanced" or "Not Balanced"
- **Amount Tracking**: Displays bank total, system total, and difference
- **Selection Count**: Shows number of selected items in each panel
- **Currency Information**: Indicates any currency conversions applied

#### Successful Reconciliation
When transactions are successfully reconciled:
1. **Status Updates**: Payment records change to "Reconciled" status
2. **Link Creation**: Bank statement lines linked to payment records
3. **Selection Reset**: Interface clears selections for next reconciliation set
4. **Notification**: Success message confirms reconciliation completion

### Finalizing the Statement
After reconciling all possible transactions:
1. **Review Remaining Items**: Check for unmatched transactions
2. **Handle Write-Offs**: Process small discrepancies through write-off feature
3. **Investigate Large Items**: Review significant unmatched amounts
4. **Document Decisions**: Maintain record of reconciliation decisions

### Next Statement Preparation
- **Outstanding Items**: Unmatched transactions will appear in future reconciliations
- **Timing Differences**: Items may resolve when subsequent [bank statements](bank-statements.md) are processed
- **Follow-up Required**: Track items needing investigation or correction

---

## Best Practices

### Preparation
- **Enable Reconciliation**: Ensure company setting is activated before beginning
- **Complete Statements**: Verify [bank statements](bank-statements.md) are fully entered with all transactions
- **Payment Verification**: Confirm all system payments are in "Confirmed" status
- **Currency Check**: Review multi-currency transactions for accuracy

### Reconciliation Process
- **Systematic Approach**: Work through transactions methodically
- **Real-time Validation**: Use the balance indicator to verify matching
- **Immediate Write-offs**: Handle small discrepancies promptly using write-off feature
- **Documentation**: Maintain clear reasons for all write-off entries

### Quality Control
- **Regular Reconciliation**: Perform reconciliation promptly after statement creation
- **Review Unmatched Items**: Investigate significant discrepancies thoroughly
- **Follow-up Tracking**: Monitor outstanding items across multiple periods
- **User Training**: Ensure team understands the interactive interface

---

## Common Scenarios

### Scenario 1: Standard Reconciliation Process
Reconcile a monthly statement with perfect matches:

1. Access reconciliation from bank statement list
2. Select matching bank transaction and system payment
3. Verify balance shows "Balanced" 
4. Click "Reconcile Selected"
5. System updates payment status and clears selections
6. Repeat for remaining transactions

### Scenario 2: Write-Off Small Bank Fee
Handle a bank maintenance fee:

1. Identify unmatched bank statement line for $15 fee
2. Click write-off action (X icon) on the bank transaction
3. Select "Bank Charges" expense account
4. Enter reason: "Monthly maintenance fee"
5. System creates journal entry and marks line as reconciled

### Scenario 3: Multi-Currency Payment
Reconcile a USD payment against IQD bank statement:

1. Select USD payment from system payments panel
2. System automatically converts to IQD at current rate
3. Select corresponding IQD bank statement line
4. Verify totals are balanced (allowing for minor conversion differences)
5. Complete reconciliation normally

---

## Troubleshooting

### Common Issues

**Problem**: Reconcile action not visible
**Solution**:
- Check that "Enable Reconciliation" is turned on in company settings
- Verify user has appropriate permissions
- Confirm bank statement exists and is accessible

**Problem**: Totals don't balance exactly
**Solution**:
- Check for currency conversion differences
- Verify transaction amounts are entered correctly
- Look for partial payments or fees
- Use write-off for immaterial differences

**Problem**: No payments appear in system payments panel
**Solution**:
- Verify payments are in "Confirmed" status
- Check that payments haven't already been reconciled
- Ensure payments belong to the correct company and journal

**Problem**: Cannot complete reconciliation
**Solution**:
- Ensure totals are balanced (difference = 0)
- Verify at least one item is selected in each panel
- Check for system errors or validation issues
- Try refreshing the interface if needed

---

## Integration with Bank Statements

Bank reconciliation is tightly integrated with the [bank statement management](bank-statements.md) system:

### Data Flow
1. **Statement Creation**: [Bank statements](bank-statements.md) provide the external transaction data
2. **Reconciliation Process**: This interface matches external data with internal payments  
3. **Status Updates**: Reconciled items are marked and linked in both systems
4. **Reporting**: Combined data supports comprehensive cash management reporting

### Workflow Sequence
1. **Create Bank Statement**: Start with [bank statement creation](bank-statements.md)
2. **Enter Transactions**: Add all bank transactions to the statement
3. **Validate Statement**: Ensure starting/ending balances are correct
4. **Begin Reconciliation**: Use this reconciliation process to match transactions
5. **Complete Cycle**: Prepare for next period's bank statement and reconciliation

---

Bank reconciliation ensures the accuracy of your cash records and provides confidence in your financial reporting. The interactive interface makes the matching process efficient while maintaining complete audit trails and proper accounting controls.
