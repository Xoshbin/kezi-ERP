---
title: Inter-Warehouse Transfers
icon: heroicon-o-arrow-path
order: 13
---

This comprehensive guide explains how to manage inter-warehouse transfers using the two-step ship and receive workflow, covering transfer creation, in-transit tracking, and complete visibility of stock movements between warehouses. Written for all users — warehouse managers, logistics coordinators, and accountants — it provides practical guidance for managing complex multi-location inventory operations.

---

## What are Inter-Warehouse Transfers?

Inter-warehouse transfers are specialized stock movements that track goods moving between different warehouse locations using a two-step workflow (ship → receive) with in-transit visibility, ensuring complete control and accountability during the transfer process.

- **Two-Step Workflow**: Separate ship and receive operations for better control
- **In-Transit Tracking**: Monitor stock while in transit between locations
- **Stock Reservation**: Reserve stock at source when transfer is confirmed
- **Complete Audit Trail**: Track who shipped, when, and who received
- **Flexible Processing**: Support for partial shipments and receipts
- **Dashboard Visibility**: Real-time view of transfers in progress

**Accounting Purpose**: Inter-warehouse transfers maintain accurate inventory records across multiple locations while providing the audit trails needed for internal control and reconciliation between warehouses.

---

## System Requirements

### Location Configuration
- **Source Location**: Internal warehouse location with available stock
- **Destination Location**: Internal warehouse location for receiving stock
- **Transit Location**: Virtual location for tracking in-transit goods (auto-created if needed)

### Prerequisites
1. **Multiple Warehouses**: At least two internal warehouse locations configured
2. **Products**: Storable products with inventory tracking enabled
3. **User Permissions**: Access to create and process stock pickings
4. **Stock Availability**: Sufficient stock at source location

---

## Where to find it in the UI

Navigate to **Inventory → Stock Pickings**

Filter by **Type: Internal** to view transfer orders

Inter-warehouse transfers also appear in:
- **Dashboard**: Transfers in transit widget
- **Stock Quantities**: Reserved quantities at source
- **Stock Movements**: Detailed movement history
- **Reports**: Transfer history and in-transit reports

**Tip**: The header's Help/Docs button opens this guide.

---

## Two-Step Workflow Overview

### Why Two Steps?

The two-step workflow provides:
- **Accountability**: Separate responsibility for shipping and receiving
- **Visibility**: Track goods while in transit
- **Control**: Verify quantities at both ends
- **Flexibility**: Handle delays between ship and receive
- **Audit Trail**: Complete record of who did what and when

### Workflow States

```
Draft → Confirmed → Shipped → Done
```

**Draft**: Transfer order created, editable
**Confirmed**: Stock reserved at source, ready to ship
**Shipped**: Goods in transit, awaiting receipt
**Done**: Transfer complete, stock at destination

---

## Creating a Transfer Order

Navigate to **Inventory → Stock Pickings** → **Create Stock Picking**

### Step 1: Basic Information

**Transfer Type**:
- **Type**: Select "Internal" for inter-warehouse transfers
- **Reference**: Auto-generated transfer number
- **Scheduled Date**: When the transfer should occur

**Location Configuration**:
- **Source Location**: Warehouse sending the stock
- **Transit Location**: Leave blank (auto-created) or select existing
- **Destination Location**: Warehouse receiving the stock

### Step 2: Product Lines

**Adding Products**:
1. Click **Add Product Line**
2. **Product**: Select storable product
3. **Quantity**: Amount to transfer
4. **From Location**: Defaults to source location
5. **To Location**: Defaults to destination location

**Multiple Products**:
- Add as many product lines as needed
- Each line can have different quantities
- System validates stock availability

### Step 3: Additional Details

**Optional Information**:
- **Notes**: Internal notes about the transfer
- **Origin**: Reference to originating document
- **Priority**: Transfer priority level

**Save Options**:
- **Save as Draft**: Continue editing later
- **Save and Confirm**: Immediately confirm the transfer

---

## Step 1: Confirming and Shipping

### Confirming the Transfer

Navigate to transfer order → Click **Confirm**

**What Happens**:
- **Stock Reservation**: Stock reserved at source location
- **Status Change**: Transfer moves to "Confirmed" state
- **Validation**: System validates stock availability
- **Lock**: Transfer details become locked

**Stock Impact**:
- **Available Quantity**: Reduced by transfer amount
- **Reserved Quantity**: Increased by transfer amount
- **Physical Quantity**: Unchanged (still at source)

### Shipping the Transfer

Navigate to confirmed transfer → Click **Ship**

**Ship Action**:
- **Stock Movement**: Creates movement from source to transit
- **Status Change**: Transfer moves to "Shipped" state
- **Timestamps**: Records shipped date and time
- **User Tracking**: Records who performed the shipment

**What Happens**:
1. **Stock Moves Created**: From source to transit location
2. **Physical Stock Updated**: Removed from source warehouse
3. **Transit Stock Created**: Added to in-transit location
4. **Reservation Released**: Stock no longer reserved at source

**Verification**:
- **Source Location**: Stock quantity decreased
- **Transit Location**: Stock quantity increased
- **Transfer Status**: Shows "Shipped"
- **Shipped By**: Shows user who shipped
- **Shipped At**: Shows shipment timestamp

---

## Step 2: Receiving the Transfer

### Receive Action

Navigate to shipped transfer → Click **Receive**

**Receive Process**:
- **Stock Movement**: Creates movement from transit to destination
- **Status Change**: Transfer moves to "Done" state
- **Timestamps**: Records received date and time
- **User Tracking**: Records who performed the receipt
- **Completion**: Marks transfer as complete

**What Happens**:
1. **Stock Moves Created**: From transit to destination location
2. **Physical Stock Updated**: Removed from transit, added to destination
3. **Transfer Completed**: Status set to "Done"
4. **Audit Trail**: Complete record of transfer lifecycle

**Verification**:
- **Transit Location**: Stock quantity decreased to zero
- **Destination Location**: Stock quantity increased
- **Transfer Status**: Shows "Done"
- **Received By**: Shows user who received
- **Received At**: Shows receipt timestamp
- **Completed At**: Shows completion timestamp

---

## Monitoring In-Transit Stock

### Viewing Transfers in Transit

Navigate to **Inventory → Stock Pickings**

**Filter Options**:
- **Type**: Internal
- **State**: Shipped
- **Date Range**: Shipped date range

**In-Transit Information**:
- **Transfer Reference**: Unique transfer number
- **Products**: Items in transit
- **Quantities**: Amounts being transferred
- **Source/Destination**: Where from and where to
- **Shipped Date**: When goods left source
- **Shipped By**: Who performed shipment
- **Expected Arrival**: Scheduled or estimated date

### Dashboard Widget

**Transfers in Transit Widget**:
- **Count**: Number of transfers currently in transit
- **Total Value**: Value of goods in transit
- **Aging**: How long transfers have been in transit
- **Alerts**: Overdue or delayed transfers

### Stock Quantities View

Navigate to **Inventory → Stock Quantities**

**Transit Location**:
- **Filter by Location**: Select transit location
- **Products in Transit**: All products currently moving
- **Quantities**: Amounts in transit by product
- **Source/Destination**: Where goods are coming from/going to

---

## Simple vs. Two-Step Transfers

### When to Use Simple Transfers

Use simple one-step internal transfers when:
- **Same Location**: Moving within same physical warehouse
- **Immediate**: Transfer happens instantly
- **No Transit Time**: No time between ship and receive
- **Single Person**: Same person handles both ends
- **Simple Tracking**: Basic movement record sufficient

**Example**: Moving stock from "Warehouse A - Zone 1" to "Warehouse A - Zone 2"

### When to Use Two-Step Transfers

Use two-step inter-warehouse transfers when:
- **Different Locations**: Moving between separate warehouses
- **Transit Time**: Goods spend time in transit
- **Different People**: Shipping and receiving handled by different staff
- **Accountability**: Need separate ship and receive verification
- **Tracking**: Need visibility while goods are in transit

**Example**: Moving stock from "Warehouse A - Main" to "Warehouse B - Distribution Center"

---

## Best Practices

### 1. Transfer Planning
- **Batch Transfers**: Combine multiple products in single transfer
- **Scheduled Transfers**: Plan regular transfer schedules
- **Stock Levels**: Maintain appropriate stock at each location
- **Lead Times**: Account for transit time in planning

### 2. Shipping Process
- **Verification**: Verify quantities before shipping
- **Documentation**: Include packing lists with shipments
- **Tracking**: Use carrier tracking for physical shipments
- **Communication**: Notify receiving warehouse of shipments

### 3. Receiving Process
- **Inspection**: Inspect goods upon receipt
- **Quantity Verification**: Verify received quantities match shipped
- **Discrepancy Handling**: Document and resolve any discrepancies
- **Timely Processing**: Process receipts promptly

### 4. In-Transit Management
- **Regular Monitoring**: Check in-transit transfers regularly
- **Aging Analysis**: Monitor transfers in transit too long
- **Follow-up**: Contact shipping/receiving for delayed transfers
- **Exception Handling**: Establish procedures for lost or damaged goods

---

## Handling Exceptions

### Partial Shipments

**Scenario**: Only part of transfer quantity can be shipped

**Current Limitation**: System ships full quantities
**Workaround**: 
1. Cancel original transfer
2. Create new transfer for available quantity
3. Create second transfer for remaining quantity

**Future Enhancement**: Partial shipment support planned

### Partial Receipts

**Scenario**: Only part of shipped quantity is received

**Current Limitation**: System receives full quantities
**Workaround**:
1. Receive full transfer
2. Create adjustment for missing quantity
3. Create new transfer for missing items

**Future Enhancement**: Partial receipt support planned

### Damaged Goods in Transit

**Scenario**: Goods damaged during transit

**Process**:
1. **Receive Transfer**: Complete the receive process
2. **Adjustment**: Create inventory adjustment for damaged goods
3. **Documentation**: Document damage with photos/reports
4. **Claim Processing**: Process insurance or carrier claims

### Lost Shipments

**Scenario**: Shipment never arrives at destination

**Process**:
1. **Investigation**: Verify shipment status with carrier
2. **Waiting Period**: Allow reasonable time for delivery
3. **Adjustment**: Create inventory adjustment if shipment confirmed lost
4. **Insurance Claim**: Process insurance claim if applicable

---

## Reporting and Analysis

### Transfer History Reports

**Report Contents**:
- **Transfer Details**: Reference, dates, quantities
- **Status Tracking**: Current status of each transfer
- **User Activity**: Who created, shipped, received
- **Timing Analysis**: Time between ship and receive
- **Exception Tracking**: Delayed or problematic transfers

### In-Transit Analysis

**Key Metrics**:
- **Average Transit Time**: Time between ship and receive
- **In-Transit Value**: Value of goods currently in transit
- **Transfer Volume**: Number of transfers by period
- **Location Pairs**: Most common source/destination combinations

### Performance Metrics

**Efficiency Indicators**:
- **Processing Time**: Time to ship after confirmation
- **Receipt Time**: Time to receive after shipment
- **Accuracy Rate**: Percentage of transfers without discrepancies
- **On-Time Performance**: Percentage of transfers completed on schedule

---

## Troubleshooting

### Common Issues

**Q: Why can't I see the Ship button?**
A: The Ship button only appears for Internal type transfers in Confirmed state. Verify the transfer type is "Internal" and status is "Confirmed".

**Q: Why can't I see the Receive button?**
A: The Receive button only appears for transfers in Shipped state. Verify the transfer has been shipped first.

**Q: Where did my in-transit stock go?**
A: Check the Transit location in Stock Quantities. Stock in transit appears in the transit location until received.

**Q: Can I cancel a shipped transfer?**
A: No, shipped transfers cannot be cancelled. You must receive the transfer, then create a reverse transfer if needed.

**Q: How do I handle quantity discrepancies?**
A: Currently, receive the full transfer then create an inventory adjustment for the discrepancy. Document the reason for the adjustment.

**Q: Can I edit a confirmed transfer?**
A: No, confirmed transfers are locked. Cancel the transfer to return it to draft, make changes, then confirm again.

---

## Integration with Other Modules

The inter-warehouse transfer system integrates with:

- **[Stock Movements](stock-movements.md)**: Detailed movement records for each transfer step
- **[Stock Management](stock-management.md)**: Overall stock tracking across locations
- **[Inventory Management](inventory-management.md)**: Complete inventory system overview

---

## Related Documentation

- [Stock Movements](stock-movements.md) - Detailed movement processing
- [Stock Management](stock-management.md) - Basic stock tracking
- [Inventory Management](inventory-management.md) - Complete system overview
- [Stock Locations](stock-locations.md) - Location hierarchy and management

---

Inter-warehouse transfers provide the control, visibility, and accountability needed for managing inventory across multiple warehouse locations, ensuring accurate stock records and complete audit trails for all inter-location movements.
