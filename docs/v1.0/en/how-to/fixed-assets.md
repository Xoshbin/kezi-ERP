# Fixed Assets Management

Fixed assets are long-term tangible resources used in business operations, such as buildings, machinery, equipment, and vehicles. Their cost is allocated over their useful life through depreciation, ensuring expenses are matched with the revenue they help generate.

Jmeryar NotebookLM provides a comprehensive Fixed Assets management system that allows you to track assets, calculate depreciation automatically, and manage disposals.

## 1. Accessing Fixed Assets

To access the Fixed Assets module:
1. Navigate to **Accounting** in the main menu.
2. Click on **Assets** under the **Financial Planning** group.

## 2. Creating an Asset

You can record a new asset manually:

1. Click the **Create Asset** button.
2. Fill in the **Asset Currency Info**:
    *   **Name**: A descriptive name for the asset (e.g., "Delivery Truck 2024").
    *   **Currency**: The currency used for the purchase.
    *   **Current Exchange Rate**: Automatically fetched if the currency differs from the company default.
3. Fill in the **Asset Details**:
    *   **Purchase Date**: The date the asset was acquired.
    *   **Purchase Value**: The original cost of the asset.
    *   **Salvage Value**: The estimated value of the asset at the end of its useful life (value you expect to sell it for).
    *   **Useful Life**: The number of years you expect to use the asset.
    *   **Prorata Temporis**: Enable this if you want depreciation to be calculated based on the exact number of days the asset was held in the first period, rather than a full period's depreciation.
    *   **Depreciation Method**: Choose how the asset value decreases over time (see below).
    *   **Declining Factor**: (Visible only for Declining Balance method) The multiplication factor for the rate.
4. Configure the **Accounting Accounts**:
    *   **Asset Account**: The balance sheet account tracking the asset's cost (Fixed Asset type).
    *   **Depreciation Expense Account**: The expense account where depreciation costs are recorded.
    *   **Accumulated Depreciation Account**: The contra-asset account accumulating total depreciation.
5. Click **Create** or **Create & Create Another**.

## 3. Depreciation Methods

Jmeryar supports two primary depreciation methods:

### Straight Line
The most common and simplest method. It spreads the cost evenly over the useful life of the asset.
*   **Formula**: (Purchase Value - Salvage Value) / Useful Life

### Declining Balance
An accelerated depreciation method that records higher depreciation expenses in the earlier years of an asset's life.
*   **Formula**: Book Value at Beginning of Year × (Straight Line Rate × Declining Factor)

## 4. Asset Lifecycle

### Draft
New assets start in the **Draft** status. In this state, you can modify all details. Depreciation does not run for draft assets.

### Confirmed
Once an asset is reviewed and verified, it moves to **Confirmed**. This locks the core details and prepares it for depreciation.

### Depreciating
When the depreciation process starts running, the status changes to **Depreciating**.

### Fully Depreciated
When the asset's book value reaches the salvage value (or zero), the status updates to **Fully Depreciated**.

### Sold / Disposed
If you sell or scrap the asset before it's fully depreciated, it is marked as **Sold**.

## 5. Depreciation Entries

Jmeryar automates the calculation and posting of depreciation entries.

1. Open a specific **Asset**.
2. Scroll to the **Depreciation Entries** section.
3. You will see a schedule of calculated depreciation.
4. Entries can be **Posted** to the general ledger to officially record the expense.
