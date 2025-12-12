---
title: Inventory Management
icon: heroicon-o-cube
order: 4
---

# Inventory Management: Complete System Overview

This comprehensive guide explains how the complete inventory management system works in your accounting system, covering all aspects from basic stock tracking to advanced lot management and reporting. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is Inventory Management?

Inventory management is a comprehensive system that tracks, values, and manages all aspects of your stock throughout its lifecycle, from purchase to sale, providing complete visibility and control over your inventory operations.

- **Real-time Tracking**: Monitor stock quantities, locations, and movements in real-time
- **Multi-method Valuation**: Support for FIFO, LIFO, AVCO, and Standard Price valuation methods
- **Lot Tracking**: Complete traceability with lot codes and expiration dates
- **Reservation System**: Reserve stock for specific orders and prevent overselling
- **Reordering Rules**: Automated reorder suggestions based on min/max levels
- **Comprehensive Reporting**: Valuation, aging, turnover, and traceability reports

**Accounting Purpose**: The inventory management system ensures accurate inventory valuation for balance sheet reporting, proper cost of goods sold calculation, and complete audit trails for all inventory transactions.

---

## System Requirements

### Product Configuration
- **Storable Products**: Products must be configured as "Storable Product" type for inventory tracking
- **Valuation Methods**: Choose from FIFO, LIFO, AVCO (Average Cost), or Standard Price methods
- **Account Setup**: Inventory asset, COGS expense, and stock input liability accounts must be configured
- **Location Structure**: Warehouse locations and customer/vendor locations must be established

### Prerequisites
1. **Chart of Accounts**: Inventory asset, COGS expense, and stock input liability accounts configured
2. **Product Catalog**: Products set up with appropriate inventory configuration and accounts
3. **Location Hierarchy**: Warehouse, customer, and vendor locations established
4. **User Permissions**: Access to inventory management, accounting, and reporting features
5. **Currency Setup**: Base currency and exchange rates configured for multi-currency operations

---

## Where to find it in the UI

Navigate to **Inventory** cluster in the main navigation

The inventory management system includes these main sections:
- **Products**: Manage product catalog and inventory settings
- **Stock Quantities**: View current stock levels by product and location
- **Stock Movements**: Track all inventory movements and create manual adjustments
- **Stock Locations**: Manage warehouse locations and location hierarchy
- **Lots**: Track lot codes, expiration dates, and lot-specific movements
- **Reordering Rules**: Set up automatic reorder suggestions
- **Reports**: Access valuation, aging, turnover, and traceability reports
- **Dashboard**: Overview widgets showing key inventory metrics

**Tip**: The header's Help/Docs button opens relevant guides for each section.

---

## Core Inventory Operations

### Managing Stock Quantities

Navigate to **Inventory → Stock Quantities**

**Current Stock View**:
- **Product**: Product name and SKU
- **Location**: Warehouse location
- **Quantity**: Current available quantity
- **Reserved**: Quantity reserved for orders
- **Available**: Quantity available for new orders (Quantity - Reserved)
- **Lot**: Associated lot code (if lot tracking enabled)

**Key Actions**:
- **Filter by Product**: Search for specific products
- **Filter by Location**: View stock in specific locations
- **View Details**: Click any row to see detailed stock information

### Creating Stock Movements

Navigate to **Inventory → Stock Movements**

**Step 1: Create New Movement**
Click **Create Stock Movement**

**Step 2: Basic Information**
- **Product**: Select the product to move
- **From Location**: Source location (warehouse, vendor, customer)
- **To Location**: Destination location
- **Quantity**: Amount to move (positive number)
- **Movement Type**: Receipt, Delivery, Internal Transfer, or Adjustment
- **Reference**: Optional reference number for tracking

**Step 3: Additional Details**
- **Move Date**: Date of the movement (defaults to today)
- **Lot**: Select specific lot (if lot tracking enabled)
- **Notes**: Optional description or comments

**Step 4: Confirm Movement**
- **Draft**: Save as draft for later completion
- **Confirm**: Lock the movement details
- **Process**: Complete the movement and update stock levels

### Product Inventory Configuration

Navigate to **Inventory → Products**

**Step 1: Basic Product Setup**
- **Name**: Product description
- **SKU**: Unique product code
- **Type**: Select "Storable Product" for inventory tracking
- **Unit Price**: Default selling price

**Step 2: Inventory Settings**
- **Valuation Method**: Choose FIFO, LIFO, AVCO, or Standard Price
- **Track Lots**: Enable lot tracking for traceability
- **Default Inventory Account**: Asset account for inventory value
- **Income Account**: Revenue account for sales
- **Expense Account**: COGS account for cost recognition

**Step 3: Initial Stock (Optional)**
Create an initial stock movement to set opening balances

---

## Valuation Methods Explained

### FIFO (First In, First Out)
- **Concept**: Oldest inventory is consumed first
- **Cost Tracking**: Maintains separate cost layers for each purchase
- **Best For**: Perishable goods, rising cost environments
- **Accounting**: Creates detailed cost layers with purchase dates and costs

### LIFO (Last In, First Out)
- **Concept**: Newest inventory is consumed first
- **Cost Tracking**: Maintains separate cost layers for each purchase
- **Best For**: Non-perishable goods, falling cost environments
- **Accounting**: Uses most recent costs for COGS calculation

### AVCO (Average Cost)
- **Concept**: Weighted average cost calculation
- **Cost Tracking**: Single average cost maintained per product
- **Best For**: Commodities, stable cost environments
- **Accounting**: Recalculates average cost with each receipt

### Standard Price
- **Concept**: Fixed cost per unit
- **Cost Tracking**: Variances tracked in price difference accounts
- **Best For**: Manufacturing with standard costing
- **Accounting**: Price variances recorded separately

---

## Lot Tracking and Traceability

### Enabling Lot Tracking

Navigate to **Inventory → Products** → Select Product → Edit

**Configuration**:
- **Track Lots**: Enable checkbox
- **Lot Generation**: Manual or automatic lot code generation
- **Expiration Tracking**: Enable for perishable goods

### Managing Lots

Navigate to **Inventory → Lots**

**Creating Lots**:
- **Lot Code**: Unique identifier (e.g., LOT-2024-001)
- **Product**: Associated product
- **Expiration Date**: For perishable goods
- **Notes**: Additional information

**Lot Movements**:
All stock movements with lot tracking show:
- **Source Lot**: Lot being consumed
- **Destination Lot**: Lot being created (for production)
- **Quantity**: Amount moved per lot

### FEFO (First Expired, First Out)

The system automatically suggests FEFO allocation for lot-tracked products:
- **Expiration Priority**: Lots closest to expiration are consumed first
- **Automatic Allocation**: System suggests optimal lot consumption
- **Override Capability**: Manual lot selection when needed

---

## Reordering Rules and Automation

### Setting Up Reordering Rules

Navigate to **Inventory → Reordering Rules**

**Step 1: Create Rule**
- **Product**: Select product for reordering
- **Location**: Warehouse location
- **Minimum Quantity**: Reorder trigger level
- **Maximum Quantity**: Target stock level
- **Safety Stock**: Buffer quantity for demand variability

**Step 2: Lead Time Configuration**
- **Lead Time Days**: Supplier delivery time
- **Procurement Type**: Purchase or Manufacturing
- **Preferred Vendor**: Default supplier

**Step 3: Rule Activation**
- **Active**: Enable automatic suggestions
- **MTO (Make to Order)**: Special handling for custom orders

### Reorder Suggestions

The system generates automatic reorder suggestions:
- **Below Minimum**: Products requiring immediate reordering
- **Suggested Quantity**: Calculated reorder amount
- **Priority**: Based on stock level and demand
- **Action Required**: Create purchase orders or manufacturing orders

---

## Inventory Reporting

### Valuation Reports

Navigate to **Inventory → Reports → Valuation Report**

**Report Parameters**:
- **As of Date**: Valuation date
- **Product Filter**: Specific products or all
- **Include Reconciliation**: Compare with GL balances

**Report Contents**:
- **Product Details**: Name, SKU, valuation method
- **Quantities**: On-hand, reserved, available
- **Unit Costs**: Current cost per unit
- **Total Values**: Extended inventory values
- **GL Reconciliation**: Comparison with accounting records

### Aging Reports

Navigate to **Inventory → Reports → Aging Report**

**Aging Buckets**:
- **0-30 days**: Recent inventory
- **31-60 days**: Moderate aging
- **61-90 days**: Older inventory
- **90+ days**: Slow-moving stock

**Analysis Features**:
- **Value by Age**: Inventory value in each bucket
- **Turnover Metrics**: Movement frequency
- **Expiration Alerts**: Lots approaching expiration

### Turnover Analysis

Navigate to **Inventory → Reports → Turnover Report**

**Key Metrics**:
- **Turnover Ratio**: Cost of goods sold ÷ Average inventory
- **Days Sales Outstanding**: Average days to sell inventory
- **Fast/Slow Movers**: Products by movement frequency
- **Trend Analysis**: Historical turnover patterns

---

## Best Practices

### 1. Product Configuration
- **Consistent Valuation**: Use same method for similar products
- **Account Mapping**: Dedicated accounts for different product categories
- **Lot Tracking**: Enable for products requiring traceability

### 2. Location Management
- **Logical Structure**: Organize locations by warehouse and zone
- **Clear Naming**: Use descriptive location names
- **Access Control**: Restrict access to sensitive locations

### 3. Movement Processing
- **Timely Confirmation**: Process movements promptly
- **Reference Numbers**: Use clear references for tracking
- **Regular Reviews**: Monitor movement patterns and exceptions

### 4. Reporting and Analysis
- **Regular Valuation**: Monthly inventory valuation reports
- **Aging Monitoring**: Track slow-moving inventory
- **Reorder Optimization**: Adjust min/max levels based on demand

---

## Integration with Other Modules

The inventory management system integrates seamlessly with:

- **[Vendor Bills](vendor-bills.md)**: Automatic stock receipts when posting bills for storable products
- **[Customer Invoices](customer-invoices.md)**: Automatic stock deliveries when posting invoices
- **[Payments](payments.md)**: Financial aspects of inventory transactions
- **[Bank Reconciliation](bank-reconciliation.md)**: Reconcile payments for inventory purchases

---

## Related Documentation

- [Stock Movements](stock-movements.md) - Detailed movement processing guide
- [Lot Tracking](lot-tracking.md) - Complete lot management guide  
- [Reordering Rules](reordering-rules.md) - Automated procurement setup
- [Stock Management](stock-management.md) - Basic stock tracking guide

---

This inventory management system provides complete control over your inventory operations while maintaining accurate accounting records and providing the insights needed for effective inventory management decisions.
