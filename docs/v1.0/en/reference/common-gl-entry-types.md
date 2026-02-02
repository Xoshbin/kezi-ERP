---
title: Common GL Entry Types
icon: heroicon-o-table-cells
---

# Common GL Entry Types

This reference guide provides a quick lookup for common accounting transactions and how they affect your General Ledger (GL).

---

## Standard Business Operations (Automatic)

These entries are typically generated automatically by source documents.

| Transaction Type | Debit Account | Credit Account | Trigger Action |
| :--- | :--- | :--- | :--- |
| **Sales Invoice** | Accounts Receivable | Revenue / Output VAT | Validating Invoice |
| **Customer Payment** | Bank / Cash | Accounts Receivable | Posting Payment |
| **Vendor Bill** | Expense / Asset / Input VAT | Accounts Payable | Validating Bill |
| **Vendor Payment** | Accounts Payable | Bank / Cash | Posting Payment |
| **Stock Receipt** | Inventory (Asset) | Inventory Valuation (Liability/Interim) | Validating Receipt |
| **Cash Transfer** | Bank (Destination) | Bank (Source) | Internal Transfer |

---

## Common Manual Adjustments

These entries are usually recorded manually using the **Journal Entries** module.

### 1. Asset Depreciation
Recording the monthly loss of value for equipment or vehicles.

| Line | Account Type | Account Name (Example) | Debit | Credit |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Expense | Depreciation Expense | ✅ | |
| 2 | Asset (Contra) | Accumulated Depreciation | | ✅ |

### 2. Accrued Expenses
Recording an expense that has been incurred but not yet billed (e.g., electricity for the current month).

| Line | Account Type | Account Name (Example) | Debit | Credit |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Expense | Utilities Expense | ✅ | |
| 2 | Liability | Accrued Liabilities | | ✅ |

### 3. Prepaid Expenses
Paying for something in advance (e.g., annual insurance).

| Line | Account Type | Account Name (Example) | Debit | Credit |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Asset | Prepaid Insurance | ✅ | |
| 2 | Asset | Bank / Cash | | ✅ |

### 4. Correcting a Mispost
Moving a transaction from an incorrect account to the correct one.

| Line | Account Type | Account Name (Example) | Debit | Credit |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Expense | Correct Account (e.g., Office Supplies) | ✅ | |
| 2 | Expense | Incorrect Account (e.g., Repairs) | | ✅ |

---

## The Golden Rules of Debit and Credit

| Account Category | Increase (+) | Decrease (-) | Normal Balance |
| :--- | :--- | :--- | :--- |
| **Asset** | Debit | Credit | Debit |
| **Liability** | Credit | Debit | Credit |
| **Equity** | Credit | Debit | Credit |
| **Revenue** | Credit | Debit | Credit |
| **Expense** | Debit | Credit | Debit |

---

## Related Documentation
- [Manual Journal Entries](../how-to/journal-entries.md) - Step-by-step recording guide.
- [Automatic Journal Flow](../explanation/automatic-journal-flow.md) - Understanding background accounting.
