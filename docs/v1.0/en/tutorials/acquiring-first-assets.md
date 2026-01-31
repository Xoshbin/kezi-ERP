# Tutorial: Acquiring Your First Company Assets

In this tutorial, you will learn how to purchase equipment for your company (like a car or a computer) and ensure it is tracked automatically as an asset.

## Prerequisite
You should have recorded your initial capital injection (see [Story One: Recording Initial Capital](recording-initial-capital.md)).

---

## Scenario: Buying Office Computers
Imagine you are buying 5 high-performance computers for your new office from **Paykar Tech Supplies** for a total of **5,000,000 IQD**.

### Step 1: Create the Vendor Bill
Instead of just recording an expense, we will use a **Vendor Bill** to create the asset.

![Vendor Bills List View](/docs/images/vendor-bills-list.png)

1.  Navigate to **Accounting → Purchases → Vendor Bills**.
2.  Click **Create**.

![Create Vendor Bill Form](/docs/images/vendor-bill-create.png)

3.  Select **Paykar Tech Supplies** as the Vendor.
4.  Enter a **Bill Reference** (e.g., `INV-2026-001`).

### Step 2: Configure the Asset Category
Before adding the computers, we need a "template" that tells the system how to handle them.

1.  In the **Line Items** section, click **Add to lines**.
2.  Click the **gear icon (Settings)** on the line item to open the **Advanced Settings**.

![Asset Category Selection](/docs/images/vendor-bill-asset-category.png)

3.  In the **Shipping & Assets** section, click the **"+"** button next to **Category** to create a new category.
3.  Fill in the details:
    *   **Category Name**: `Office Electronics`
    *   **Asset Account**: `Office Equipment` (Balance Sheet)
    *   **Useful Life**: `3` years
    *   **Depreciation Method**: `Straight Line`
4.  Click **Create**.

### Step 3: Complete the Purchase
1.  Enter the **Quantity** (5) and **Unit Price** (1,000,000 IQD).
2.  Ensure the **Category** you just created is selected.
3.  Click the yellow **Create** button at the bottom to save the draft.
4.  Review the totals and click **Post**.

### Step 4: Verify the Asset
Now, let's see the result of your work!

1.  Navigate to **Accounting → Assets**.
2.  You will see a new record named after your bill line (e.g., "High-Performance Office Computers").
3.  Click on it to see the **Depreciation Schedule**—the system has already calculated exactly how much value this asset will lose every year for the next 3 years!

---

## What did we achieve?
By using this workflow, you didn't just record a payment; you created a permanent record of a company resource that will be automatically managed by the ERP for years to come.

**Next Steps:**
- Learn the theory behind these numbers in [Understanding Asset Management](../explanation/understanding-asset-management.md).
- Learn how to dispose of an asset when you sell it in the [Fixed Assets How-to Guide](../how-to/fixed-assets.md).
