# Diátaxis Documentation Migration Tracker

This report tracks the migration of Jmeryar NotebookLM documentation to the **Diátaxis framework**. The goal is to provide a world-class documentation experience by separating content into four distinct categories:
- **Tutorials**: Learning-oriented (The "Story" series).
- **How-to Guides**: Task-oriented (How to achieve a specific goal).
- **Explanations**: Understanding-oriented (Deeper concepts/theory).
- **References**: Information-oriented (Technical specs, comparisons).

---

## 🚦 Migration Status Overview

| Module | Status | Total Docs | Completed |
| :--- | :--- | :---: | :---: |
| Accounting | 🟢 Completed | 15 | 15 |
| Inventory | 🟢 Completed | 10 | 10 |
| HR & Payroll | 🔴 Pending | 5 | 0 |
| Manufacturing | 🔴 Pending | 4 | 0 |
| Projects | 🔴 Pending | 4 | 0 |
| Core / System | 🔴 Pending | 5 | 0 |

---

## 📚 Migration Checklist

### 🏗️ Accounting & Finance
- [x] **Fixed Assets** (Migrated)
    - [x] [Tutorial: Acquiring Assets (Story Two)](v1.0/en/tutorials/story-two.md)
    - [x] [How-to: Manage Fixed Assets](v1.0/en/how-to/fixed-assets.md)
    - [x] [Explanation: Understanding Asset Management](v1.0/en/explanation/understanding-asset-management.md)
    - [x] [Reference: Asset Acquisition Methods](v1.0/en/reference/asset-acquisition-methods.md)
- [x] **Vendor Bills** (Migrated)
    - [x] [Tutorial: Recording Your First Bill](v1.0/en/tutorials/recording-your-first-vendor-bill.md)
    - [x] [How-to: Manage Vendor Bills](v1.0/en/how-to/vendor-bills.md)
    - [x] [Explanation: Understanding Vendor Bills](v1.0/en/explanation/understanding-vendor-bills.md)
- [x] **Journal Entries** (Migrated)
    - [x] [How-to: Manual Journal Entries](v1.0/en/how-to/journal-entries.md)
    - [x] [Explanation: Automatic Journal Flow](v1.0/en/explanation/automatic-journal-flow.md)
    - [x] [Reference: Common GL Entry Types](v1.0/en/reference/common-gl-entry-types.md)
- [x] **Payments & Bank Recon** (Migrated)
    - [x] [Tutorial: Paying Your First Vendor Bill](v1.0/en/tutorials/paying-vendor-bill.md)
    - [x] [How-to: Recording Payments](v1.0/en/how-to/recording-payments.md)
    - [x] [How-to: Bank Reconciliation](v1.0/en/how-to/bank-reconciliation.md)
    - [x] [Explanation: Understanding Reconciliation](v1.0/en/explanation/understanding-reconciliation.md)
    - [x] [Reference: Payment Methods & Statuses](v1.0/en/reference/payment-methods.md)
- [x] **Opening Balances** (Migrated)
    - [x] [Tutorial: Setting Up Opening Balances](v1.0/en/tutorials/setting-opening-balances.md)
    - [x] [How-to: Recording Opening Balances](v1.0/en/how-to/recording-opening-balances.md)
    - [x] [Explanation: Opening Balance Concepts](v1.0/en/explanation/opening-balance-concepts.md)
    - [x] [Reference: Import Templates](v1.0/en/reference/opening-balance-import-template.md)
- [x] **Financial Reports** (Migrated)
    - [x] [Tutorial: Month-End Review](v1.0/en/tutorials/month-end-review.md)
    - [x] [How-to: Generating Financial Reports](v1.0/en/how-to/generating-financial-reports.md)
    - [x] [Explanation: Financial Reporting Concepts](v1.0/en/explanation/financial-reporting-concepts.md)
    - [x] [Reference: Financial Report Terms](v1.0/en/reference/financial-report-terms.md)

### 📦 Inventory & Warehouse
- [ ] **Inventory Management** (In Progress)
    - [x] [Tutorial: Your first warehouse setup](v1.0/en/tutorials/your-first-warehouse-setup.md)
    - [x] [How-to: Managing Stock](v1.0/en/how-to/managing-stock.md)
    - [x] [How-to: Stock Picking](v1.0/en/how-to/stock-picking.md)
    - [x] [How-to: Stock Movements](v1.0/en/how-to/stock-movements.md)
    - [x] [How-to: Warehouse Transfers](v1.0/en/how-to/inter-warehouse-transfers.md)
    - [x] [How-to: Landed Costs](v1.0/en/how-to/landed-costs.md)
    - [x] [Explanation: Inventory Concepts](v1.0/en/explanation/inventory-concepts.md)
    - [x] [Explanation: Inventory Architecture](v1.0/en/explanation/inventory-architecture.md)
    - [x] [Explanation: Warehouse Transfers](v1.0/en/explanation/warehouse-transfers.md)
    - [x] [Explanation: Landed Costs](v1.0/en/explanation/landed-costs.md)
    - [x] [Reference: Inventory Fields](v1.0/en/reference/inventory-fields.md)
    - [x] [Reference: Warehouse Transfers](v1.0/en/reference/warehouse-transfers.md)
    - [x] [Reference: Landed Costs](v1.0/en/reference/landed-costs.md)
    - [x] [Reference: Stock Movements](v1.0/en/reference/stock-movements.md)
    - [x] Note: Refactored `inventory-management`, `landed-costs`, `inter-warehouse-transfers`, and `stock-movements`.

### 👥 Human Resources
- [ ] **Employee Management**
- [ ] **Payroll Processing**
- [ ] **Leave & Attendance**

### 🏭 Manufacturing (MRP)
- [ ] **Bill of Materials (BoM)**
- [ ] **Manufacturing Orders**
- [ ] **Work Centers**

---

## 🛠️ How to Migrate a Document (Standards)

When picking up a document from the "Pending" list, follow these standard steps:

1.  **Identify the Core Task**: What is the user trying to *do*? (How-to).
2.  **Extract Concepts**: Is there a lot of "Why" or "What is" text? Move it to `explanation/`.
3.  **Find Technical Details**: Are there tables, field lists, or specs? Move them to `reference/`.
4.  **Create a Narrative (Optional)**: Can this be part of a "Story" tutorial? Add it to `tutorials/`.
5.  **Cross-Link**: Ensure all four quadrants link to each other using relative paths (e.g., `../explanation/doc.md`).
6.  **Verify Parity**: Run the `DocumentationConsistencyTest` to ensure Kurdish and Arabic translations are in sync.

---

## 📸 Screenshot Standards

Consistency in visuals is key for a high-quality documentation suite. Follow these rules for all screenshots:

### 1. Storage Location
*   **Directory**: All images must be stored in the project's public directory:
    *   Path: `public/docs/images/`
    *   Format: `kebab-case-descriptive-name.png` (e.g., `vendor-bill-create.png`)

### 2. Language Parity
*   **English (en)**: Use **English** interface screenshots.
*   **Arabic (ar)**: Use **English** interface screenshots (verified decision: English screens are acceptable for Arabic docs).
*   **Kurdish (ckb)**: Use **localized Kurdish** interface screenshots.
    *   File naming convention: suffix with `-ckb` (e.g., `vendor-bill-create-ckb.png`).

### 3. Embedding Syntax
*   Always use root-relative paths starting with `/docs/images/`:
    ```markdown
    ![Description](/docs/images/filename.png)
    ```

---

> [!IMPORTANT]
> Always run `php artisan test --filter=DocumentationConsistencyTest` after updating any documentation to ensure system parity.
