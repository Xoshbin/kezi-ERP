# Bank Reconciliation: Ensuring Cash Accuracy and Financial Control

This comprehensive guide explains how bank reconciliation works in your accounting system, covering the matching process, write-offs, and multi-currency handling. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is Bank Reconciliation?

Bank reconciliation is the process of comparing your company's cash records with the bank statement to identify and resolve differences between your internal payment records and the bank's transaction records.

- **Verify Accuracy**: Ensure internal records match bank records
- **Identify Discrepancies**: Find mistakes or timing differences
- **Update Records**: Mark payments as reconciled when matched
- **Handle Unmatched Items**: Write off small discrepancies or investigate larger ones

**Accounting Principle**: Your payment records should match the bank statement transactions after accounting for timing differences, ensuring accurate cash reporting.

---

## System Requirements

### Company Configuration
- **Enable Reconciliation**: Must be activated in company settings
- **Default Accounts**: Bank accounts and reconciliation accounts must be configured
- **User Permissions**: Users need appropriate access to reconciliation features

### Prerequisites
1. **Bank Statement Created**: [Bank statement](bank-statements.md) must exist with transaction lines
2. **Payment Records**: System payments must be confirmed and ready for matching
3. **Currency Alignment**: Both bank statements and payments should use compatible currencies

---

## Where to find it in the UI

Navigate to **Accounting → Banking & Cash → Bank Statements**

Bank reconciliation also appears in:
- **Bank Statement List**: "Reconcile" action button (green scale icon)
- **Payment Records**: Reconciliation status indicators

**Tip**: The reconcile action is only visible when reconciliation is enabled in company settings.

---

## Starting Bank Reconciliation

Navigate to **Accounting → Banking & Cash → Bank Statements**

### Step 1: Access Reconciliation Interface

**Find Bank Statement**: Locate the statement you want to reconcile in the list
**Click Reconcile Action**: Use the "Reconcile" button (green scale icon)
**Access Control**: Action only appears when reconciliation is enabled

### Step 2: Understanding the Interface Layout

The reconciliation screen uses an interactive three-panel layout:

**Left Panel - Bank Transactions**: Shows unreconciled bank statement lines
- View all available bank transactions
- Select transactions using checkboxes
- Real-time total calculation

**Right Panel - System Payments**: Displays confirmed payments without reconciliation
- View confirmed, unreconciled payments
- Multi-currency support with automatic conversion
- Select payments using checkboxes

**Bottom Panel - Reconciliation Summary**: Real-time balance validation
- Bank total (sum of selected bank items)
- System total (sum of selected payments)
- Difference calculation
- Balance status indicator

### Step 3: Matching Transactions

**Select Bank Transactions**: Click checkboxes in the left panel to select bank statement lines
**Select System Payments**: Click checkboxes in the right panel to select corresponding payments
**Monitor Balance**: Watch the summary panel for "Balanced" status
**Execute Reconciliation**: Click "Reconcile Selected" when totals match

## Multi-Currency Reconciliation

### Automatic Currency Conversion
The system handles multi-currency scenarios automatically:

**Different Currency Payments**: Payments in different currencies are converted to statement currency
**Real-time Rates**: Uses current exchange rates for calculations
**Display Information**: Shows both original and converted amounts
**Balance Calculation**: All totals calculated in statement currency

### Currency Indicators
- **Same Currency**: Green indicator for matching currencies
- **Different Currency**: Yellow indicator showing conversion applied
- **Conversion Details**: Hover or click for rate information

**Example**: Reconciling a USD payment against an IQD bank statement:
- System Payment: $500 USD 
- Exchange Rate: 1 USD = 1,310 IQD
- Converted Amount: 655,000 IQD
- Bank Statement Line: 655,000 IQD (matches after conversion)

---

## Write-Off Feature

### Handling Unmatched Items

For small discrepancies or bank fees without corresponding system payments:

### Step 1: Identify Unmatched Transaction

**Review Bank Statement Lines**: Find transactions without corresponding payments
**Common Examples**: Bank fees, interest charges, small discrepancies

### Step 2: Execute Write-Off

**Click Write-Off Action**: Use the "X" icon in the bank transactions table
**Select Expense Account**: Choose appropriate account (e.g., "Bank Charges")
**Enter Reason**: Provide clear explanation for the write-off
**Create Entry**: System generates journal entry and marks line as reconciled

### Write-Off Examples
- **Bank Fees**: Monthly maintenance fees ($15-25)
- **Interest Charges**: Overdraft fees, loan interest  
- **Small Discrepancies**: Rounding differences under $5

---

## Journal Entry Impact

### Successful Reconciliation
```
No journal entry created - marking existing payments as reconciled
```

### Write-Off Transaction
```
Dr. Bank Charges Expense    $25.00
    Cr. Bank Account              $25.00
```

The write-off creates a journal entry to record the bank fee or discrepancy while marking the bank statement line as reconciled.

---

## Common Scenarios

### Scenario 1: Standard Reconciliation Process
Perfect matching of bank and system records:

**Steps**:
1. Access reconciliation from bank statement list
2. Select matching bank transaction ($1,500 wire transfer)
3. Select corresponding system payment ($1,500 vendor payment)
4. Verify balance shows "Balanced" with zero difference
5. Click "Reconcile Selected" to complete matching

**Result**:
- Payment status updated to "Reconciled"
- Bank statement line linked to payment record
- Interface clears selections for next set

### Scenario 2: Write-Off Small Bank Fee
Handle bank maintenance fee without corresponding payment:

**Steps**:
1. Identify unmatched bank statement line: -$25.00 "Monthly Service Fee"
2. Click write-off action (X icon) on the bank transaction
3. Select "Bank Service Charges" expense account
4. Enter reason: "Monthly account maintenance fee"
5. System creates expense entry and marks line reconciled

**Result**:
- Journal entry: Dr. Bank Service Charges $25, Cr. Bank Account $25
- Bank statement line marked as reconciled
- No further action needed

### Scenario 3: Multi-Currency Payment Reconciliation
Reconcile USD payment against IQD bank statement:

**Example**:
- Bank Statement: 655,000 IQD outgoing transfer
- System Payment: $500 USD vendor payment  
- Exchange Rate: 1 USD = 1,310 IQD (captured at payment time)
- Converted Amount: 655,000 IQD

**Steps**:
1. Select IQD bank statement line (655,000 IQD)
2. Select USD system payment ($500 USD)
3. System shows conversion: $500 × 1,310 = 655,000 IQD
4. Verify totals balance exactly
5. Complete reconciliation normally

**Result**:
- Multi-currency reconciliation completed
- Exchange rate documentation preserved
- Both records marked as reconciled

---

## Best Practices

### Preparation
- **Enable Reconciliation**: Activate company setting before starting
- **Complete Statements**: Ensure all bank transactions are entered in [bank statements](bank-statements.md)
- **Payment Verification**: Confirm system payments are in "Confirmed" status
- **Currency Accuracy**: Review multi-currency transactions for proper rates

### Reconciliation Process  
- **Systematic Approach**: Work through transactions methodically by date or amount
- **Real-time Validation**: Monitor balance indicator continuously during selection
- **Immediate Write-offs**: Handle small discrepancies promptly using write-off feature
- **Clear Documentation**: Provide detailed reasons for all write-off entries

### Quality Control
- **Regular Reconciliation**: Perform within days of receiving bank statements
- **Review Unmatched Items**: Investigate discrepancies over predetermined threshold
- **Follow-up Tracking**: Monitor outstanding items across multiple periods
- **User Training**: Ensure team understands the interactive interface features

### Audit and Compliance
- **Audit Trail**: System maintains complete reconciliation history
- **Supporting Documentation**: Link bank statements and payment confirmations
- **Review Process**: Implement supervisor review for large write-offs
- **Month-end Procedures**: Complete all reconciliations before closing periods

---

## Troubleshooting

### Common Issues

**Q: Reconcile action not visible on bank statement**
A: Check reconciliation settings and permissions:
- Verify "Enable Reconciliation" is activated in company settings
- Confirm user has appropriate permissions for reconciliation
- Ensure bank statement exists and is accessible

**Q: Totals don't balance exactly in multi-currency reconciliation**
A: Review currency conversion handling:
- Check exchange rates used for payment and reconciliation
- Verify amounts are entered correctly in source documents
- Consider minor rounding differences (typically under $1)
- Use write-off for immaterial conversion differences

**Q: No payments appear in system payments panel**
A: Verify payment status and filters:
- Ensure payments are in "Confirmed" status (not Draft)
- Check that payments haven't already been reconciled
- Verify payments belong to correct company and bank account
- Refresh browser if interface appears stale

**Q: Cannot complete reconciliation process**
A: Validate selection and balancing:
- Ensure totals are balanced (difference = 0)
- Verify at least one item is selected in each panel
- Check for validation errors or system messages
- Try clearing selections and starting over if needed

### Error Messages

**"Reconciliation not enabled for this company"**
- Contact administrator to enable reconciliation in company settings
- Verify company configuration includes reconciliation permissions

**"Selected payments total does not match bank total"**
- Review selected amounts carefully
- Check for currency conversion issues
- Consider using write-off for small differences

---

## Frequently Asked Questions

**Q: What happens to payments after they are reconciled?**
A: Reconciled payments are marked with "Reconciled" status and linked to specific bank statement lines. They will not appear in future reconciliation interfaces but remain visible in payment records with reconciliation indicators.

**Q: Can I undo a reconciliation if I made a mistake?**
A: The system maintains audit trails but reconciliations should be considered permanent. Contact your system administrator if you need to reverse reconciliation entries for correction.

**Q: How do I handle bank transactions that span multiple periods?**
A: Bank transactions should be reconciled in the period they appear on the bank statement. If system payments were recorded in a different period, they can still be matched during reconciliation.

**Q: What should I do with large unmatched items?**
A: Investigate thoroughly before writing off. Check for missing system payments, incorrect amounts, or timing differences. Large discrepancies may indicate errors requiring correction rather than write-off.

---

## Related Documentation

- [Bank Statements](bank-statements.md) - Creating and managing bank statement records
- [Payments](payments.md) - Understanding payment creation and management

---

Bank reconciliation ensures accuracy of cash records and provides confidence in financial reporting. The interactive interface streamlines the matching process while maintaining complete audit trails and proper accounting controls.
