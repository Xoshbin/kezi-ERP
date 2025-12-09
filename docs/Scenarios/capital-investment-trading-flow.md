# Capital Investment & Trading Cycle - Manual Test Scenario

**Objective**: Verify the end-to-end business flow from Capital Investment to Trading (Purchase & Sales) with a focus on "Offers", Inventory Management, and Financial Analysis.

**Departments Involved**:
- **Owner/Finance**: Capital Injection
- **Purchase Department**: Getting offers (RFQ), Buying (PO)
- **Inventory Department**: Receiving and Delivering goods
- **Sales Department**: Sending offers (Quotations), Selling (SO)
- **Accounting Department**: Invoicing, Payments, and Analysis

---

## Phase 1: Capital Investment (Finance)

### Step 1: Initial Capital Injection
**Scenario**: The business owner invests capital to start operations.
**Role**: Accountant / Owner

1.  **Navigate** to **Accounting > Journal Entries**.
2.  **Click** `Create`.
3.  **Fill** the form:
    - **Journal**: Miscellaneous Operations
    - **Reference**: `CAP-INV-001`
    - **Lines**:
        - Line 1: Account `Cash (USD)` | Debit: `$100,000`
        - Line 2: Account `Owner's Equity` | Credit: `$100,000`
4.  **Click** `State: Posted`.
5.  **Outcome & Expectation**:
    - Status changes to `Posted`.
    - Dashboard/Accounts show `$100,000` in Cash.

---

## Phase 2: Purchasing (Getting Offers & Buying)

### Step 2: Request for Quotation (Getting Offers)
**Scenario**: Purchase Department requests a price quote from a vendor for 50 Laptops.
**Role**: Purchase Manager

1.  **Navigate** to **Purchases > Orders**.
2.  **Click** `Create`.
3.  **Fill** the form:
    - **Vendor**: `Paykar Tech Supplies`
    - **Order Deadline**: (Select a date)
    - **Lines**:
        - Product: `Laptop Pro` (Create if not exists, set as 'Storable Product')
        - Quantity: `50`
        - Unit Price: `0` (Since we are asking for an offer, or putting target price e.g., `$800`).
4.  **Action**: This created record is a **Request for Quotation (RFQ)**.
5.  **Outcome & Expectation**:
    - State is `Draft` (RFQ).
    - You can `Print` or `Send by Email` to the vendor.

### Step 3: Purchase Order (Buying Items)
**Scenario**: Vendor accepts the offer/price of $800. We confirm the buy order.
**Role**: Purchase Manager

1.  **Open** the RFQ from Step 2.
2.  **Edit** the line price to `$800` (Agreed Price).
3.  **Click** `Confirm Order`.
4.  **Outcome & Expectation**:
    - State changes to `Purchase Order`.
    - A `Receipt` (Inventory Move) is generated/scheduled.
    - **Inventory Dept** is notified (conceptually) of incoming goods.

---

## Phase 3: Inventory Management (Buying Side)

### Step 4: Receive Products
**Scenario**: The 50 Laptops arrive at the warehouse.
**Role**: Inventory Manager

1.  **Navigate** to **Inventory > Stock Pickings**.
2.  **Locate** the pending receipt (The 'Source Document' column will match your PO number, e.g., `PO-000XX`).
3.  **Open** the record.
4.  **Check** the Operations lines:
    - Demand: `50`.
    - Quantity: `50` (Click `Set Quantities` or manually enter `50` to confirm full receipt).
5.  **Click** `Validate`.
6.  **Outcome & Expectation**:
    - State changes to `Done`.
    - **Product Quantity** for `Laptop Pro` updates to `50` Units On Hand.
    - **Accounting Move** (Stock Valuation) is created automatically (Debit Inventory, Credit Interim Receipt).

---

## Phase 4: Purchasing (Financial Settlement)

### Step 5: Vendor Bill (Paying)
**Scenario**: Vendor sends the bill for the laptops.
**Role**: Accountant

1.  **Navigate** to the **Purchase Order** (Purchases > Orders).
2.  **Click** `Create Bill` (Header Action).
3.  **Review** the Draft Bill:
    - Vendor: `Paykar Tech Supplies`
    - Lines: 50 x $800 = $40,000.
4.  **Set** `Bill Date`: Today.
5.  **Click** `Confirm`.
6.  **Outcome & Expectation**:
    - Bill State changes to `Posted`.
    - Journal Entry created: Debit `Inventory/Expenses` (depending on settings), Credit `Accounts Payable`.

### Step 6: Pay Purchase Bill
**Scenario**: Pay the vendor using the invested capital.
**Role**: Accountant

1.  **On the Bill**, click `Register Payment`.
2.  **Select** Journal: `Cash (USD)` (or Bank).
3.  **Amount**: `$40,000`.
4.  **Click** `Create Payment`.
5.  **Outcome & Expectation**:
    - Bill status: `In Payment` / `Paid`.
    - Cash balance reduces by $40,000.

---

## Phase 5: Sales Operations (Sending Offers & Selling)

### Step 7: Sales Quotation (Sending Offers)
**Scenario**: A client wants to buy 10 Laptops. We send them an offer.
**Role**: Sales Person

1.  **Navigate** to **Sales > Orders**.
2.  **Click** `Create`.
3.  **Fill** the form:
    - **Customer**: `Hawre Trading Group`
    - **Lines**:
        - Product: `Laptop Pro`
        - Quantity: `10`
        - Unit Price: `$1,200` (Selling Price).
4.  **Action**: Save. This is a **Quotation**.
5.  **Outcome & Expectation**:
    - State is `Draft` (Quotation).
    - Can send to customer.

### Step 8: Sales Order (Selling Items)
**Scenario**: Client accepts the offer. We confirm the sale.
**Role**: Sales Manager

1.  **Open** the Quotation from Step 7.
2.  **Click** `Confirm`.
3.  **Outcome & Expectation**:
    - State changes to `Sales Order`.
    - A `Delivery` order is generated (Search in Inventory > Stock Pickings).

---

## Phase 6: Inventory Management (Selling Side)

### Step 9: Deliver Products (Detailed Workflow)
**Scenario**: Warehouse ships the 10 Laptops. This involves a multi-step verification process to ensure inventory accuracy.
**Role**: Inventory Manager

1.  **Navigate** to **Inventory > Stock Pickings**.
2.  **Locate** the pending delivery (The 'Source Document' column will match your SO number, e.g., `SO-000XX`).
3.  **Open** the record. Status is initially **Draft** or **Confirmed**.

#### Sub-step 9.1: Confirm Picking
*   **Action**: If status is `Draft`, click `Confirm`.
*   **Effect**:
    *   State changes to `Confirmed`.
    *   System acknowledges the requirement to move these items.
    *   Generates specific `Stock Moves` for each line item.
*   **If Skinned/Fails**: You cannot reserve stock. The system treats this as a theoretical plan rather than an actionable order.

#### Sub-step 9.2: Assign (Check Availability & Reserve)
*   **Action**: Click `Assign`.
*   **Effect**:
    *   **Reserves Stock**: System locks `10` units of "Laptop Pro" specifically for this order. They are no longer "Free to Use" for other orders.
    *   **Allocates Lots**: If the product uses lots, specific Serial Numbers are chosen (FEFO).
    *   State changes to `Assigned`.
*   **If Skipped/Fails**:
    *   Stock remains "Free to Use". You might accidentally sell the same laptops to another customer (**Double Booking Risk**).
    *   Pickers won't know which specific lot/shelf to pull from.

#### Sub-step 9.3: Validate (Ship)
*   **Action**: Click `Validate`.
*   **Effect**:
    *   **Deducts Inventory**: The 10 units actally leave the system. Quantity On Hand drops from `50` to `40`.
    *   **Financial Impact**: Creates the Journal Entry for Cost of Goods Sold (Debit COGS, Credit Inventory).
    *   State changes to `Done`.
*   **If Skipped/Fails**:
    *   **Inventory Mismatch**: You physically shipped the goods, but the system thinks you still have them.
    *   **Financial Error**: COGS is never recorded, inflating your profit artificially (Profit = Sales - 0 instead of Sales - Cost).

---

## Phase 7: Sales (Financial Settlement)

### Step 10: Customer Invoice
**Scenario**: Bill the client for the delivered items.
**Role**: Accountant

1.  **Navigate** to the **Sales Order**.
2.  **Click** `Create Invoice`.
3.  **Select** `Regular Invoice`.
4.  **Click** `Create and View Invoice`.
5.  **Review** the Draft Invoice ($12,000).
6.  **Click** `Confirm`.
7.  **Outcome & Expectation**:
    - Invoice State changes to `Posted`.
    - Journal Entry created: Debit `Accounts Receivable`, Credit `Product Sales`.

### Step 11: Receive Payment
**Scenario**: Client pays the invoice.
**Role**: Accountant

1.  **On the Invoice**, click `Register Payment`.
2.  **Select** Journal: `Cash (USD)`.
3.  **Amount**: `$12,000`.
4.  **Click** `Create Payment`.
5.  **Outcome & Expectation**:
    - Invoice status: `Paid`.
    - Cash balance increases by $12,000.

---

## Phase 8: Analysis (Checking Numbers)

### Step 12: Analyze Financials
**Scenario**: Review the profitability and current position.
**Role**: Finance Manager

1.  **Navigate** to **Accounting > Reporting > Profit & Loss**.
    - **Expectation**:
        - **Income**: $12,000 (Sales).
        - **Expenses (COGS)**: $8,000 (10 units * $800 cost).
        - **Net Profit**: $4,000.

2.  **Navigate** to **Accounting > Reporting > Balance Sheet**.
    - **Expectation**:
        - **Assets**:
            - Cash: $72,000 ($100k start - $40k buy + $12k sell).
            - Inventory: $32,000 (40 units * $800 cost).
            - Total Assets: $104,000.
        - **Equity**:
            - Capital: $100,000.
            - Current Year Earnings: $4,000.
            - Total Equity: $104,000.
        - **Check**: Assets ($104k) = Equity ($104k).

3.  **Navigate** to **Inventory > Reporting > Stock Moves**.
    - **Expectation**:
        - IN: +50 Units.
        - OUT: -10 Units.
        - Balance: 40 Units.
