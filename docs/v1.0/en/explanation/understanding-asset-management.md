# Understanding Asset Management

This document explains the core principles behind Fixed Asset management in Kezi , focusing on the "Why" rather than the "How".

---

## 1. Capex vs. Opex
In accounting, we distinguish between two types of spending:
*   **Operating Expenses (Opex):** Small items that are "used up" quickly (e.g., printer paper, electricity). These hit your Profit & Loss statement immediately.
*   **Capital Expenditure (Capex):** High-value resources that provide value for years (e.g., a car, a building). These are recorded as **Assets** on your Balance Sheet.

## 2. The Concept of Depreciation
If you buy a car for 30,000,000 IQD, your company hasn't "lost" that money—it just moved it from Cash to an Asset. However, the car loses value as you drive it.

**Depreciation** is the way we record this loss of value over time. It allows the business to match the cost of the asset with the revenue it helps generate over its lifetime (the Matching Principle).

## 3. The Three Key Accounts
Every asset in the ERP relies on three accounts working together:

| Account Type | Purpose | Impact |
| :--- | :--- | :--- |
| **Asset Account** | Holds the original purchase price. | Balance Sheet (Debit) |
| **Accumulated Depreciation** | A "Contra-Account" that tracks total wear-and-tear. | Balance Sheet (Credit) |
| **Depreciation Expense** | Records the loss of value for the current period. | Profit & Loss (Debit) |

**Book Value Calculation:**
`Purchase Price - Accumulated Depreciation = Book Value`

## 4. Why Use Asset Categories?
Consistency is vital for auditing. **Asset Categories** serve as standardized templates. They ensure that every time an employee buys a "Laptop", the system uses the same 3-year life and the same GL accounts. This eliminates human error and keeps your financial reports clean.

---

**Learn More:**
- Follow the [Acquiring Your First Assets Tutorial](../tutorials/acquiring-first-assets.md).
- See technical details in [Asset Acquisition Methods (Reference)](../reference/asset-acquisition-methods.md).
