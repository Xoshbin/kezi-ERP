---
title: Bank Statements
icon: heroicon-o-document-text
order: 2
---


This comprehensive guide explains how bank statements work in your accounting system, covering creation, transaction entry, multi-currency support, and validation processes. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What are Bank Statements?

Bank statements are records of all transactions that occurred in your bank accounts during a specific period, providing the external verification needed for cash management and reconciliation.

- **Transaction Recording**: Document all deposits, withdrawals, and bank charges
- **Balance Verification**: Track opening and closing balances for each period
- **Reconciliation Foundation**: Provide external data for matching with internal payment records
- **Cash Flow Tracking**: Enable accurate monitoring of actual cash movements

**Accounting Purpose**: Bank statements serve as independent verification of cash transactions, ensuring your internal records match the bank's records through the reconciliation process.

---

## System Requirements

### Journal Configuration
- **Bank Journal**: Must be configured with "Bank" journal type
- **Currency Setting**: Journal currency must match statement currency
- **Account Setup**: Bank account must be properly linked to journal

### Prerequisites
1. **Bank Journal Setup**: At least one bank journal must be configured
2. **User Permissions**: Appropriate access to banking and cash management features
3. **Company Configuration**: Banking features enabled in company settings

---

## Where to find it in the UI

Navigate to **Accounting → Banking & Cash → Bank Statements**

Bank statements also appear in:
- **Create Action**: "Create" button for new statement entry
- **Statement List**: Overview of all statements with reconciliation status
- **Payment Records**: Related bank statement information

**Tip**: The header's Help/Docs button opens this guide.

---

## Creating Bank Statements

Navigate to **Accounting → Banking & Cash → Bank Statements → Create**

### Step 1: Statement Header Information

**Currency**: Select the statement currency (defaults to company currency)
**Bank Journal**: Select the bank journal (must be of type "Bank")
**Reference**: Enter statement number or identifier (e.g., "STMT-2024-001")
**Date**: Enter the statement date
**Starting Balance**: Enter opening balance from previous statement
**Ending Balance**: Enter closing balance from current statement

### Step 2: Transaction Entry

**Date**: Transaction date from bank statement
**Description**: Bank's description of the transaction
**Partner**: Link to customer/vendor if applicable (optional)
**Amount**: Transaction amount
- Positive values for deposits, transfers in, interest earned
- Negative values for withdrawals, checks, fees, transfers out

**Foreign Currency Fields** (optional for multi-currency transactions):
- **Foreign Currency**: Select the original transaction currency
- **Amount in Foreign Currency**: Enter the original amount before conversion

### Step 3: Validation and Save

**Balance Validation**: System automatically calculates ending balance
**Review Lines**: Verify all transactions are entered correctly
**Save Statement**: Create the statement and prepare it for reconciliation

---

## Multi-Currency Bank Statements

### Foreign Currency Transaction Support

**Statement Currency**: All amounts stored in the statement's base currency
**Original Currency**: Track the original transaction currency and amount
**Exchange Rates**: Conversion handled during transaction entry
**Display**: Both original and converted amounts visible

### Multi-Currency Bank Accounts

**Currency Matching**: Each statement must match the journal's currency
**Conversion Rates**: Applied during transaction entry
**Reconciliation**: Currency differences handled automatically during reconciliation

**Example**: USD bank account statement with EUR transaction:
- Statement Currency: USD
- Transaction: -$95.50 (converted from -€85.00 at rate 1.1235)
- Original Amount: €85.00 EUR
- Converted Amount: $95.50 USD

---

## Statement Validation

### Balance Verification Process

**Calculated Ending Balance** = Starting Balance + Sum of All Transactions
**Must Equal**: Bank's ending balance on statement

### Common Validation Issues

**Balance Mismatch**: Calculated balance ≠ stated ending balance
- Review all transaction amounts and signs
- Check for missing or duplicate entries
- Verify starting balance accuracy

**Missing Transactions**: Statement appears incomplete
- Add missing deposits or withdrawals
- Check statement for fees or charges
- Verify all transaction dates

**Duplicate Entries**: Same transaction entered twice
- Remove duplicate lines from statement
- Check for similar amounts on same date

---

## Common Scenarios

### Scenario 1: Monthly Statement Creation
Create a complete monthly bank statement:

**Steps**:
1. Navigate to Bank Statements → Create
2. Select IQD currency and main bank journal
3. Enter statement reference "STMT-2024-03" and date
4. Input starting balance: 5,000,000 IQD
5. Add 25 transaction lines (deposits and withdrawals)
6. Verify calculated ending balance matches bank: 4,750,000 IQD
7. Save statement and proceed to reconciliation

**Result**:
- Complete bank statement ready for reconciliation
- All transactions documented with proper descriptions
- Balance validation passed

### Scenario 2: Multi-Currency Transaction Entry
Record a foreign currency transaction on IQD statement:

**Example**:
- Bank Statement Currency: IQD
- Transaction: USD wire transfer payment
- Original Amount: -$500 USD
- Exchange Rate: 1 USD = 1,310 IQD
- Converted Amount: -655,000 IQD

**Steps**:
1. Add transaction line with amount: -655,000 IQD
2. Select USD as foreign currency
3. Enter -500 as foreign currency amount
4. System maintains both amounts for tracking
5. Save with dual currency documentation

**Result**:
- Transaction recorded in statement currency (IQD)
- Original currency and amount preserved
- Exchange rate documentation available

### Scenario 3: Error Correction
Correct an incorrect transaction amount before reconciliation:

**Steps**:
1. Open the statement in edit mode
2. Locate the incorrect transaction line (wrong amount)
3. Modify the amount field with correct value
4. Verify ending balance calculation updates automatically
5. Save changes
6. If already reconciled, create correcting entry instead

**Result**:
- Statement accuracy maintained
- Balance validation remains correct
- Audit trail preserved

---

## Best Practices

### Data Entry Accuracy
- **Double-Check Amounts**: Verify all transaction amounts and signs
- **Complete Descriptions**: Include meaningful transaction details from bank
- **Partner Linking**: Associate transactions with customers/vendors when possible
- **Foreign Currency**: Record original amounts for multi-currency transactions

### Workflow Management
- **Regular Entry**: Create statements promptly after receiving bank statements
- **Systematic Review**: Verify calculated balances before saving
- **Prompt Reconciliation**: Begin reconciliation process immediately after creation
- **Error Prevention**: Review all entries before finalizing

### Documentation Standards
- **Clear References**: Use consistent statement numbering (e.g., STMT-YYYY-MM)
- **Transaction Details**: Include bank's description plus internal notes
- **Date Accuracy**: Ensure transaction dates match bank statement exactly
- **Currency Clarity**: Document foreign currency transactions thoroughly

### Audit and Compliance
- **Complete Records**: Maintain all statement data for audit requirements
- **Change Tracking**: System maintains history of statement modifications
- **Access Control**: Limit statement creation to authorized accounting staff
- **Review Process**: Implement approval workflows for large transactions

---

## Troubleshooting

### Common Issues

**Q: Cannot select bank journal from dropdown**
A: Verify journal configuration and access:
- Ensure the journal type is set to "Bank"
- Verify journal belongs to the current company
- Check user permissions for journal access
- Confirm journal is active and not archived

**Q: Balance calculation doesn't match bank statement**
A: Review transaction entry accuracy:
- Check all transaction amounts and signs (positive/negative)
- Look for missing or duplicate entries
- Verify starting balance is correct from previous statement
- Ensure foreign currency conversions are accurate

**Q: Cannot edit statement after creation**
A: Check reconciliation status and permissions:
- Verify statement lines haven't been reconciled yet
- Confirm user has edit permissions for bank statements
- Consider creating correcting statement instead of editing
- Check if statement is locked due to period closure

**Q: Foreign currency amounts not calculating correctly**
A: Verify currency setup and exchange rates:
- Check that both currencies are active and configured
- Verify exchange rate is available for transaction date
- Ensure original amount matches bank statement exactly
- Review currency conversion calculations

### Error Messages

**"Starting balance cannot be modified"**
- Starting balance is typically inherited from previous statement
- Check if this is the first statement for the account
- Verify account initialization was done correctly

**"Journal type must be Bank"**
- Selected journal is not configured as bank type
- Choose a different journal or update journal configuration
- Contact administrator to set up proper bank journals

---

## Frequently Asked Questions

**Q: How often should I create bank statements?**
A: Create statements as soon as you receive them from the bank, typically monthly. More frequent statements (weekly or daily) can be created for active accounts requiring closer monitoring.

**Q: What happens if I make an error after reconciliation?**
A: Once statement lines are reconciled, avoid editing the original statement. Instead, create a new statement with correcting entries to maintain audit integrity.

**Q: Can I import bank statements automatically?**
A: The current system supports manual entry. Contact your system administrator about available import options for your specific bank format.

**Q: How do I handle bank statements in different currencies?**
A: Each bank account should have statements in its native currency. Use the foreign currency fields to track original transaction amounts when they differ from the account currency.

---

## Related Documentation

- [Bank Reconciliation](bank-reconciliation.md) - Matching statement transactions with system payments
- [Payments](payments.md) - Understanding payment creation and management

---

Bank statements provide the foundation for accurate cash management and enable reliable reconciliation processes that ensure your financial records match external bank records.
