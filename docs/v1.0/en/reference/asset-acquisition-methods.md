# Reference: Asset Acquisition Methods

This reference provides a technical comparison of the different ways to record assets in Kezi .

---

## Comparison Table

| Feature | **Vendor Bill** (Recommended) | **Manual Registration** | **Account Trigger** |
| :--- | :--- | :--- | :--- |
| **Primary Use Case** | Buying new assets from suppliers. | Recording existing assets/opening balances. | High-volume assets of same type. |
| **Automation Level** | **High.** Creates asset automatically on post. | **None.** Requires manual entry of all data. | **Medium.** Flags accounts for asset creation. |
| **Audit Trail** | Linked to Vendor, Bill, and Payment. | Standalone record. | Linked to original transaction. |
| **Data Source** | Inherits Price/Date from invoice. | Must be typed manually. | Inherits Price/Date from line item. |

---

## System Specifications

### Mandatory Fields (Draft State)
*   **Name**: Unique identifier for the asset.
*   **Company ID**: Tenant identifier.
*   **Currency ID**: Purchase currency.
*   **Purchase Date**: Must be in an open accounting period.

### Accounting Requirements
To confirm an asset, the following General Ledger accounts must be valid and active:
1.  **Asset Account**: Type = `Fixed Assets`.
2.  **Accumulated Depreciation Account**: Type = `Fixed Assets` or `Contra-Asset`.
3.  **Depreciation Expense Account**: Type = `Expense`.

### Status Transition Logic
1.  **Draft**: Editable. No depreciation calculations posted.
2.  **Confirmed**: Core financial fields locked. Scheduled entries calculated.
3.  **Depreciating**: At least one depreciation entry has been posted.
4.  **Fully Depreciated**: Book value equals salvage value.
5.  **Sold/Disposed**: Disposal journal entry created. Asset removed from active roster.

---

**See Also:**
- [How to Manage Fixed Assets](../how-to/fixed-assets.md)
- [Understanding Asset Management](../explanation/understanding-asset-management.md)
