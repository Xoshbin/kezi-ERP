# Payment Methods & Statuses

This reference guide details the technical definitions for payment methods and the lifecycle of a payment in Kezi.

## Payment Status Lifecycle

Payments move through several states. Understanding these states is crucial for accurate financial reporting.

| Status | Description | Financial Impact |
| :--- | :--- | :--- |
| **Draft** | The payment is being prepared and has no financial impact yet. | None. |
| **Confirmed** | The payment is finalized internally. | Impacts Partner Ledger and Outstanding Accounts. |
| **Reconciled** | The payment has been matched with a bank statement line. | Moves balance from Outstanding Account to Bank Account. |
| **Cancelled** | The payment was aborted. | Reverses any previous financial impact. |

## Payment Types

| Type | Direction | Ledger Interaction |
| :--- | :--- | :--- |
| **Inbound** | Customer Payment | Debits Outstanding Receipts, Credits Accounts Receivable. |
| **Outbound** | Vendor Payment | Debits Accounts Payable, Credits Outstanding Payments. |

## Payment Methods

Kezi supports various methods to categorize how money is moved.

### Manual
The standard method for bank transfers, cash payments, or any payment handled outside the system.
- **Workflow**: Record the payment in Kezi -> Perform the action (e.g., mail a check) -> Reconcile when it appears on the bank statement.

### Cheque
Used when a physical cheque is issued or received.
- **Special Features**: Tracks cheque numbers and maturity dates.
- **Maturity**: Payments remain in a special state until the cheque's maturity date is reached.

### Petty Cash
Used for small internal expenses handled via a petty cash fund.
- **Workflow**: Requires a specific Petty Cash Journal.

## Technical Field Reference (Payment Model)

| Field | Type | Description |
| :--- | :--- | :--- |
| `amount` | Money | The amount of the payment in the transaction currency. |
| `payment_date` | Date | The date the payment was initiated. |
| `journal_id` | ID | The Bank or Cash journal where the payment is recorded. |
| `payment_method` | Enum | `manual`, `cheque`, `petty_cash`. |
| `reference` | String | External reference (e.g., Bank transaction ID). |

## Related Guides
- [Understanding Reconciliation](../explanation/understanding-reconciliation.md)
- [How to Record Payments](../how-to/recording-payments.md)
