# How to Manage Fixed Assets

This guide explains how to perform specific tasks related to fixed assets in Kezi , such as configuration, manual entry, and tracking.

> [!TIP]
> **New to assets?** If you want a step-by-step walkthrough of your first purchase, follow our [Acquiring Your First Assets Tutorial](../tutorials/acquiring-first-assets.md).
> **Want to understand the theory?** Read our [Explanation of Asset Management](../explanation/understanding-asset-management.md).

---

## 1. Configure Asset Categories (Setup)

Asset categories act as templates that automate the accounting for similar items (e.g., "Vehicles" or "Office Electronics").

1.  Navigate to **Accounting → Assets → Asset Categories**.
2.  Click **Create Category**.
3.  Fill in the **Accounting Accounts**:
    *   **Asset Account**: Where the value is tracked on the Balance Sheet.
    *   **Accumulated Depreciation**: The contra-account for tracked wear and tear.
    *   **Depreciation Expense**: The P&L account for periodic costs.
4.  Set the **Useful Life** (years) and **Depreciation Method** (usually Straight Line).
5.  **Save** the category.

## 2. Record a New Asset

There are two ways to record an asset acquisition:

### A. Recommended: Via Vendor Bill
This method is best for new purchases as it links the asset to the original invoice.
1.  Create a **Vendor Bill**.
2.  On the line item, click the **gear icon** and select the **Asset Category** under the "Shipping & Assets" section.

![Asset Category Selection in Vendor Bill](/docs/images/vendor-bill-asset-category.png)

3.  **Post** the bill. The system will automatically create a draft asset.

### B. Manual Entry (For Opening Balances)
Use this if you already own the asset or didn't buy it through a standard bill.
1.  Navigate to **Accounting → Assets**.
2.  Click **Create Asset**.
3.  Enter the **Purchase Date**, **Value**, and **Salvage Value**.
4.  Select the appropriate **Asset Category** (or fill in the accounts manually).
5.  **Save** to create as Draft.

## 3. Manage the Asset Lifecycle

### Confirming an Asset
All new assets (manual or from bills) start as **Draft**. You must review and **Confirm** them to start depreciation.

### Posting Depreciation
1.  Open an asset in **Confirmed** or **Depreciating** status.
2.  Scroll to the **Depreciation Entries** section.
3.  Click **Post** on the due entries to record the expense in your General Ledger.

## 4. Disposing of an Asset
When an asset is sold, lost, or scrapped:
1.  Open the asset record.
2.  Click the **Dispose** action.
3.  Enter the **Disposal Date** and **Reason**.
4.  The system will calculate the final depreciation and gain/loss on disposal.

---

**See Also:**
- [Asset Acquisition Methods (Reference)](../reference/asset-acquisition-methods.md)
- [How to Create Vendor Bills](vendor-bills.md)
