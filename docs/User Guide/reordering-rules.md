---
title: Reordering Rules
icon: heroicon-o-arrow-path
order: 10
---


This comprehensive guide explains how to set up and manage automated reordering rules in your inventory system, covering min/max rules, safety stock calculations, lead time management, and procurement automation. Written for all users — accountants and non‑accountants — it provides practical guidance for optimizing inventory levels and reducing stockouts.

---

## What are Reordering Rules?

Reordering rules are automated systems that monitor inventory levels and generate procurement suggestions when stock falls below predetermined minimum levels, ensuring optimal inventory levels while minimizing carrying costs and stockout risks.

- **Automatic Monitoring**: Continuous monitoring of stock levels against defined thresholds
- **Min/Max Rules**: Minimum and maximum stock level definitions
- **Safety Stock**: Buffer quantities to handle demand variability
- **Lead Time Management**: Consideration of supplier delivery times
- **Procurement Suggestions**: Automatic generation of purchase recommendations
- **MTO Support**: Make-to-Order handling for custom products

**Accounting Purpose**: Reordering rules help maintain optimal inventory investment levels, reducing carrying costs while ensuring adequate stock for sales operations and maintaining accurate inventory valuations.

---

## System Requirements

### Rule Prerequisites
- **Products**: Storable products with inventory tracking enabled
- **Locations**: Warehouse locations for stock monitoring
- **Suppliers**: Preferred vendors configured for products
- **Lead Times**: Supplier delivery time information
- **Demand History**: Historical usage data for calculation

### Configuration Requirements
1. **Product Setup**: Products with inventory configuration
2. **Supplier Information**: Vendor details and lead times
3. **Location Structure**: Warehouse locations defined
4. **User Permissions**: Access to inventory management and procurement
5. **Approval Workflow**: Procurement approval processes (optional)

---

## Where to find it in the UI

Navigate to **Inventory → Reordering Rules**

Reordering rules also appear in:
- **Product Details**: Rules associated with specific products
- **Location Views**: Rules for specific warehouse locations
- **Dashboard**: Reorder alerts and suggestions
- **Reports**: Reorder status and analysis reports
- **Procurement**: Integration with purchase order generation

**Tip**: The header's Help/Docs button opens this guide.

---

## Understanding Reordering Concepts

### Min/Max Inventory Model

**Minimum Quantity**:
- **Reorder Point**: Stock level that triggers reordering
- **Calculation**: Safety Stock + (Average Daily Usage × Lead Time Days)
- **Purpose**: Prevent stockouts during normal operations

**Maximum Quantity**:
- **Target Level**: Desired stock level after reordering
- **Calculation**: Minimum Quantity + Economic Order Quantity
- **Purpose**: Optimize inventory investment and storage costs

**Safety Stock**:
- **Buffer Quantity**: Extra stock to handle demand variability
- **Calculation**: Based on demand variability and service level requirements
- **Purpose**: Protect against unexpected demand spikes or supply delays

### Lead Time Considerations

**Supplier Lead Time**:
- **Delivery Time**: Days from order placement to receipt
- **Variability**: Consideration of lead time fluctuations
- **Reliability**: Supplier performance history

**Internal Lead Time**:
- **Processing Time**: Internal order processing time
- **Quality Control**: Time for incoming inspection
- **Put-away Time**: Time to make stock available

---

## Creating Reordering Rules

Navigate to **Inventory → Reordering Rules** → **Create Reordering Rule**

### Step 1: Basic Rule Information

**Product and Location**:
- **Product**: Select product for reordering (must be storable)
- **Location**: Warehouse location to monitor
- **Active**: Enable/disable the rule
- **Priority**: Rule priority level (High, Medium, Low)

**Rule Type**:
- **Min/Max**: Standard minimum/maximum rule
- **MTO (Make to Order)**: Special handling for custom orders
- **Seasonal**: Rules with seasonal adjustments

### Step 2: Quantity Parameters

**Stock Levels**:
- **Minimum Quantity**: Reorder trigger level
- **Maximum Quantity**: Target stock level
- **Safety Stock**: Buffer quantity for demand variability
- **Multiple Quantity**: Order in multiples (e.g., case quantities)

**Calculation Assistance**:
- **Historical Usage**: System suggests based on past consumption
- **Demand Forecast**: Integration with demand planning
- **Manual Override**: Enter specific quantities based on business knowledge

### Step 3: Supplier Information

**Preferred Supplier**:
- **Vendor**: Default supplier for reordering
- **Lead Time**: Supplier delivery time in days
- **Minimum Order**: Supplier minimum order quantity
- **Price Information**: Current purchase price

**Alternative Suppliers**:
- **Backup Vendors**: Alternative suppliers if primary unavailable
- **Lead Time Comparison**: Delivery times for different suppliers
- **Price Comparison**: Cost analysis across suppliers

### Step 4: Advanced Settings

**Procurement Settings**:
- **Auto-Generate**: Automatically create purchase orders
- **Approval Required**: Require approval before ordering
- **Buyer**: Assigned buyer for this product
- **Budget Code**: Budget allocation for purchases

**Seasonal Adjustments**:
- **Seasonal Factors**: Adjust quantities for seasonal demand
- **Holiday Considerations**: Special handling for holiday periods
- **Promotional Planning**: Increased quantities for promotions

---

## Rule Monitoring and Alerts

### Automatic Monitoring

The system continuously monitors stock levels:

**Daily Checks**:
- **Stock Level Review**: Compare current stock to minimum levels
- **Demand Analysis**: Review recent consumption patterns
- **Lead Time Updates**: Check for supplier lead time changes
- **Rule Effectiveness**: Analyze rule performance

**Alert Generation**:
- **Below Minimum**: Immediate alerts when stock falls below minimum
- **Approaching Minimum**: Early warnings as stock approaches minimum
- **Overstock Alerts**: Warnings when stock exceeds maximum levels
- **Expired Rules**: Alerts for rules needing review

### Dashboard Integration

Navigate to **Inventory → Dashboard**

**Reorder Widgets**:
- **Items to Reorder**: Products requiring immediate attention
- **Suggested Quantities**: Recommended order quantities
- **Priority Levels**: Color-coded priority indicators
- **Value Analysis**: Financial impact of reorder suggestions

---

## Procurement Integration

### Purchase Order Generation

**Automatic Creation**:
- **Rule Triggers**: When stock falls below minimum
- **Quantity Calculation**: Order quantity to reach maximum level
- **Supplier Selection**: Use preferred supplier from rule
- **Approval Routing**: Send to appropriate approver

**Manual Review**:
- **Suggestion Review**: Review system suggestions before ordering
- **Quantity Adjustment**: Modify suggested quantities if needed
- **Supplier Changes**: Select different supplier if required
- **Timing Adjustments**: Adjust delivery dates

### Approval Workflow

**Approval Levels**:
- **Value-Based**: Different approval levels based on order value
- **Product-Based**: Special approvals for certain products
- **Buyer Authority**: Buyer approval limits
- **Management Override**: Management approval for exceptions

---

## MTO (Make to Order) Handling

### MTO Configuration

For products that are made or purchased specifically for customer orders:

**MTO Rules**:
- **No Stock Holding**: Minimum and maximum quantities set to zero
- **Customer Trigger**: Reordering triggered by customer orders
- **Lead Time Critical**: Accurate lead times essential for delivery promises
- **Quality Requirements**: Special quality or specification requirements

**Order Processing**:
- **Customer Order**: Triggers procurement or production
- **Supplier Coordination**: Direct coordination with suppliers
- **Delivery Scheduling**: Align supplier delivery with customer requirements
- **Quality Control**: Enhanced quality control for custom orders

---

## Rule Analysis and Optimization

### Performance Metrics

**Stockout Analysis**:
- **Stockout Frequency**: How often stockouts occur
- **Stockout Duration**: How long stockouts last
- **Lost Sales**: Revenue impact of stockouts
- **Customer Impact**: Customer satisfaction effects

**Inventory Efficiency**:
- **Turnover Rates**: How quickly inventory turns
- **Carrying Costs**: Cost of holding inventory
- **Obsolescence Risk**: Risk of inventory becoming obsolete
- **Storage Utilization**: Warehouse space efficiency

### Rule Optimization

**Parameter Adjustment**:
- **Minimum Level**: Adjust based on stockout analysis
- **Maximum Level**: Optimize based on carrying costs
- **Safety Stock**: Adjust based on demand variability
- **Lead Time**: Update based on supplier performance

**Seasonal Adjustments**:
- **Demand Patterns**: Adjust for seasonal demand changes
- **Supplier Capacity**: Consider seasonal supplier constraints
- **Storage Limitations**: Account for seasonal storage needs
- **Cash Flow**: Align with seasonal cash flow patterns

---

## Reporting and Analysis

### Reorder Status Reports

Navigate to **Inventory → Reports → Reorder Status**

**Report Contents**:
- **Product Information**: Product details and current stock
- **Rule Parameters**: Minimum, maximum, and safety stock levels
- **Current Status**: Stock position relative to rules
- **Suggested Actions**: Recommended procurement actions
- **Priority Ranking**: Items ranked by urgency

### Rule Performance Analysis

**Effectiveness Metrics**:
- **Rule Accuracy**: How well rules predict actual needs
- **Adjustment Frequency**: How often rules need modification
- **Cost Impact**: Financial impact of rule performance
- **Service Level**: Customer service level achievement

---

## Best Practices

### 1. Rule Setup
- **Accurate Data**: Use accurate lead times and demand data
- **Regular Review**: Review and update rules regularly
- **Gradual Implementation**: Start with critical products
- **Stakeholder Input**: Involve sales and operations teams

### 2. Parameter Optimization
- **Historical Analysis**: Base parameters on historical data
- **Demand Forecasting**: Incorporate demand forecasts
- **Supplier Performance**: Consider actual supplier performance
- **Seasonal Adjustments**: Account for seasonal variations

### 3. Monitoring and Maintenance
- **Regular Reviews**: Monthly or quarterly rule reviews
- **Performance Tracking**: Monitor rule effectiveness
- **Exception Handling**: Establish procedures for exceptions
- **Continuous Improvement**: Continuously refine rules

### 4. Integration Management
- **Procurement Alignment**: Align with procurement processes
- **Supplier Coordination**: Coordinate with supplier capabilities
- **Budget Planning**: Integrate with budget planning processes
- **System Integration**: Ensure integration with other systems

---

## Troubleshooting

### Common Issues

**Q: Why isn't my reordering rule triggering?**
A: Check that the rule is active, the product has sufficient demand history, and the current stock level is actually below the minimum quantity. Also verify that the location matches.

**Q: Why are the suggested quantities too high/low?**
A: Review the maximum quantity setting and demand patterns. Adjust the maximum level based on actual usage patterns and storage constraints.

**Q: How do I handle seasonal products?**
A: Use seasonal adjustment factors or create separate rules for different seasons. Consider using date-based rule activation/deactivation.

**Q: Why are purchase orders not being generated automatically?**
A: Check that auto-generation is enabled, approval workflows are configured correctly, and the preferred supplier information is complete.

---

## Related Documentation

- [Inventory Management](inventory-management.md) - Complete system overview
- [Stock Movements](stock-movements.md) - Movement processing
- [Vendor Bills](vendor-bills.md) - Purchase order processing
- [Stock Management](stock-management.md) - Basic inventory concepts

---

Reordering rules provide the automation needed to maintain optimal inventory levels, reducing manual monitoring while ensuring adequate stock availability and minimizing inventory investment.
