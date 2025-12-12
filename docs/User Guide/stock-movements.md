---
title: Stock Movements
icon: heroicon-o-arrows-right-left
order: 12
---


This comprehensive guide explains how to create, manage, and track all types of stock movements in your inventory system. Written for all users — accountants and non‑accountants — it provides practical guidance for processing receipts, deliveries, transfers, and adjustments following double‑entry accounting best practices.

---

## What are Stock Movements?

Stock movements are the fundamental transactions that track the physical flow of inventory between locations, documenting every change in stock quantities with complete audit trails and automatic accounting integration.

- **Movement Types**: Receipts, Deliveries, Internal Transfers, and Adjustments
- **Location Tracking**: From/To location recording for complete traceability
- **Automatic Valuation**: Cost calculation based on product valuation method
- **Journal Integration**: Automatic journal entries for all movements
- **Status Workflow**: Draft → Confirmed → Done progression with proper controls

**Accounting Purpose**: Stock movements ensure accurate inventory tracking and generate the journal entries needed for proper inventory valuation and cost of goods sold calculation.

---

## System Requirements

### Movement Prerequisites
- **Products**: Storable products with inventory configuration
- **Locations**: Source and destination locations must exist
- **Valuation Setup**: Product valuation methods configured
- **Account Configuration**: Inventory, COGS, and adjustment accounts set up

### User Permissions
1. **Inventory Access**: Permission to view and create stock movements
2. **Location Access**: Access to relevant warehouse locations
3. **Accounting Integration**: Understanding of journal entry impacts
4. **Approval Rights**: Authority to confirm and process movements

---

## Where to find it in the UI

Navigate to **Inventory → Stock Movements**

Stock movements also appear in:
- **Product Details**: Movement history for specific products
- **Location Views**: Movements affecting specific locations
- **Vendor Bills**: Automatic receipt movements when posting bills
- **Customer Invoices**: Automatic delivery movements when posting invoices
- **Reports**: Movement history and analysis reports

**Tip**: The header's Help/Docs button opens this guide.

---

## Movement Types Explained

### Receipt Movements
**Purpose**: Record incoming stock from vendors or production
**From Location**: Vendor location or production location
**To Location**: Warehouse location
**Accounting Impact**: Debit Inventory, Credit Stock Input

### Delivery Movements  
**Purpose**: Record outgoing stock to customers
**From Location**: Warehouse location
**To Location**: Customer location
**Accounting Impact**: Debit COGS, Credit Inventory

### Internal Transfer Movements
**Purpose**: Move stock between warehouse locations
**From Location**: Source warehouse location
**To Location**: Destination warehouse location
**Accounting Impact**: No journal entry (internal movement)

### Adjustment Movements
**Purpose**: Correct inventory discrepancies or record losses
**From Location**: Warehouse location (for decreases)
**To Location**: Adjustment location
**Accounting Impact**: Debit/Credit Inventory and Adjustment accounts

---

## Creating Stock Movements

Navigate to **Inventory → Stock Movements** → **Create Stock Movement**

### Step 1: Basic Movement Information

**Product Selection**:
- **Product**: Choose from storable products only
- **Current Stock**: System shows current quantities
- **Valuation Method**: Displays product's valuation method

**Location Configuration**:
- **From Location**: Source location (required)
- **To Location**: Destination location (required)
- **Location Type**: System validates location types for movement type

**Quantity and Reference**:
- **Quantity**: Amount to move (must be positive)
- **Reference**: Optional reference number for tracking
- **Movement Type**: Automatically determined from location types

### Step 2: Movement Details

**Date and Timing**:
- **Move Date**: Date of the movement (defaults to today)
- **Planned Date**: Optional future date for planned movements
- **Lock Date Validation**: System prevents movements before lock date

**Lot Information** (if lot tracking enabled):
- **Source Lot**: Lot being consumed (for outgoing movements)
- **Destination Lot**: Lot being created (for incoming movements)
- **FEFO Suggestion**: System suggests lots by expiration date

**Cost Information**:
- **Unit Cost**: Cost per unit (for receipts)
- **Total Value**: Calculated total movement value
- **Currency**: Movement currency (converted to base currency)

### Step 3: Additional Information

**Documentation**:
- **Notes**: Internal notes and comments
- **Attachments**: Supporting documents
- **Source Document**: Link to originating document (bill, invoice, etc.)

**Approval Workflow**:
- **Approver**: Required approver (if approval workflow enabled)
- **Priority**: Movement priority level
- **Department**: Originating department

---

## Movement Status Workflow

### Draft Status
**Characteristics**:
- **Editable**: All fields can be modified
- **No Stock Impact**: No quantities updated
- **No Accounting**: No journal entries created
- **Validation**: Basic field validation only

**Available Actions**:
- **Edit**: Modify any movement details
- **Delete**: Remove the movement entirely
- **Confirm**: Move to confirmed status

### Confirmed Status
**Characteristics**:
- **Locked Details**: Movement details cannot be changed
- **Reserved Stock**: Stock reserved for the movement
- **No Accounting**: Journal entries not yet created
- **Validation**: Full business rule validation

**Available Actions**:
- **Process**: Complete the movement
- **Cancel**: Return to draft or cancel entirely
- **View**: Read-only access to details

### Done Status
**Characteristics**:
- **Completed**: Movement fully processed
- **Stock Updated**: Quantities updated in all locations
- **Journal Posted**: Accounting entries created and posted
- **Immutable**: Cannot be modified or deleted

**Available Actions**:
- **View**: Read-only access to all details
- **Reverse**: Create reversing movement (if allowed)
- **Report**: Include in movement reports

---

## Automatic Movements

### Vendor Bill Integration

When posting vendor bills for storable products:

**Automatic Receipt Creation**:
- **From Location**: Vendor location (from bill partner)
- **To Location**: Product's default inventory location
- **Quantity**: From bill line quantities
- **Cost**: From bill line unit costs
- **Reference**: Bill number

**Accounting Sequence**:
1. **Receipt Movement**: Debit Inventory, Credit Stock Input
2. **Bill Posting**: Debit Stock Input, Credit Accounts Payable

### Customer Invoice Integration

When posting customer invoices for storable products:

**Automatic Delivery Creation**:
- **From Location**: Product's default inventory location
- **To Location**: Customer location (from invoice partner)
- **Quantity**: From invoice line quantities
- **Cost**: Based on product valuation method
- **Reference**: Invoice number

**Accounting Impact**:
- **Delivery Movement**: Debit COGS, Credit Inventory
- **Invoice Posting**: Debit Accounts Receivable, Credit Revenue

---

## Valuation and Costing

### FIFO/LIFO Cost Layers

For products using FIFO or LIFO valuation:

**Incoming Movements**:
- **New Cost Layer**: Created for each receipt
- **Layer Details**: Quantity, unit cost, purchase date
- **Remaining Quantity**: Tracks unconsumed amounts

**Outgoing Movements**:
- **Layer Consumption**: Consumes from appropriate layers
- **FIFO**: Oldest layers consumed first
- **LIFO**: Newest layers consumed first
- **Cost Calculation**: Weighted average of consumed layers

### AVCO (Average Cost)

For products using average cost valuation:

**Cost Calculation**:
- **Weighted Average**: (Total Value + New Value) ÷ (Total Quantity + New Quantity)
- **Real-time Updates**: Average cost updated with each receipt
- **Outgoing Cost**: Uses current average cost

### Standard Price

For products using standard price valuation:

**Fixed Costing**:
- **Standard Cost**: Fixed cost per unit
- **Price Variances**: Differences recorded in variance accounts
- **Variance Types**: Purchase price, usage, and efficiency variances

---

## Lot Tracking in Movements

### Lot-Tracked Products

For products with lot tracking enabled:

**Incoming Movements**:
- **Lot Assignment**: Assign to existing or create new lot
- **Lot Details**: Lot code, expiration date, supplier information
- **Quantity Tracking**: Track quantities by lot

**Outgoing Movements**:
- **Lot Selection**: Choose specific lots to consume
- **FEFO Allocation**: System suggests lots by expiration date
- **Partial Consumption**: Split lots when partially consumed

### Lot Traceability

**Forward Traceability**:
- **From Source**: Track where lot quantities went
- **Customer Delivery**: Which customers received specific lots
- **Usage History**: How lots were consumed

**Backward Traceability**:
- **To Source**: Track where lot quantities came from
- **Supplier Information**: Original supplier and receipt details
- **Production History**: Manufacturing details for produced lots

---

## Movement Reporting and Analysis

### Movement History Reports

Navigate to **Inventory → Reports → Movement History**

**Report Features**:
- **Date Range**: Filter by movement dates
- **Product Filter**: Specific products or categories
- **Location Filter**: Specific locations or warehouses
- **Movement Type**: Filter by receipt, delivery, transfer, adjustment

**Report Contents**:
- **Movement Details**: Date, reference, type, quantities
- **Location Information**: From/to locations
- **Cost Information**: Unit costs and total values
- **Status Tracking**: Movement status and approval details

### Stock Movement Analysis

**Key Metrics**:
- **Movement Frequency**: How often products move
- **Average Movement Size**: Typical quantities moved
- **Location Utilization**: Activity by location
- **Cost Trends**: Movement cost patterns over time

---

## Best Practices

### 1. Movement Creation
- **Accurate Quantities**: Verify quantities before confirming
- **Proper References**: Use clear, traceable reference numbers
- **Timely Processing**: Process movements promptly to maintain accuracy

### 2. Location Management
- **Consistent Usage**: Use locations consistently across movements
- **Clear Naming**: Ensure location names are descriptive
- **Access Control**: Restrict access to sensitive locations

### 3. Cost Management
- **Regular Reviews**: Review cost layers and average costs
- **Variance Analysis**: Monitor price variances for standard cost products
- **Currency Handling**: Ensure proper currency conversion for multi-currency movements

### 4. Audit and Compliance
- **Documentation**: Maintain supporting documentation for all movements
- **Approval Workflow**: Follow established approval procedures
- **Regular Reconciliation**: Reconcile movement records with physical counts

---

## Troubleshooting

### Common Issues

**Q: Why can't I edit a confirmed movement?**
A: Confirmed movements are locked to maintain data integrity. You can cancel the movement to return it to draft status, or create a reversing movement.

**Q: Why is my movement showing a negative cost?**
A: Check the valuation method and cost layers. For FIFO/LIFO, ensure sufficient cost layers exist. For AVCO, verify the average cost calculation.

**Q: Why can't I select a specific lot?**
A: Ensure the product has lot tracking enabled and the lot exists in the source location with sufficient quantity.

**Q: Why is my movement not creating journal entries?**
A: Journal entries are only created when movements reach "Done" status. Check that the movement has been fully processed.

---

## Related Documentation

- [Inventory Management](inventory-management.md) - Complete system overview
- [Lot Tracking](lot-tracking.md) - Detailed lot management guide
- [Vendor Bills](vendor-bills.md) - Automatic receipt processing
- [Customer Invoices](customer-invoices.md) - Automatic delivery processing

---

Stock movements form the foundation of accurate inventory tracking, providing the detailed transaction history needed for effective inventory management and accurate financial reporting.
