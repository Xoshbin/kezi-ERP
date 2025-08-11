# Stock Management User Guide

## Overview

The stock management system provides comprehensive inventory tracking and valuation capabilities for your accounting application. It supports multiple valuation methods, hierarchical warehouse structures, and complete audit trails for all stock movements.

## Key Features

### 📦 **Product Inventory Configuration**
- **Valuation Methods**: FIFO, LIFO, AVCO (Average Cost), and Standard Price
- **Inventory Accounts**: Automatic configuration of inventory asset, COGS, stock input, and price difference accounts
- **Average Cost Tracking**: Real-time calculation and display of average costs
- **Stock Movement History**: Complete audit trail of all product movements
- **Cost Layer Visibility**: View FIFO/LIFO cost layers for detailed cost tracking

### 🏢 **Stock Location Management**
- **Hierarchical Structure**: Create parent-child relationships between locations
- **Location Types**: Internal warehouses, customer locations, vendor locations, and adjustment locations
- **Multi-Company Support**: Company-specific location management
- **Active/Inactive Status**: Control which locations are available for use

### 📋 **Stock Movement Tracking**
- **Movement Types**: Incoming, Outgoing, Internal Transfer, and Adjustment
- **Status Workflow**: Draft → Confirmed → Done (with cancellation support)
- **Source Document Integration**: Link movements to vendor bills, invoices, and other documents
- **Reference Numbers**: Optional reference tracking for better organization
- **Date-based Filtering**: Find movements by date ranges

## Getting Started

### 1. Configure Products for Inventory

1. Navigate to **Products** in the Sales & Purchases section
2. Create or edit a product
3. Set the **Type** to "Storable Product"
4. In the **Inventory Management** section:
   - Choose a **Valuation Method** (FIFO, LIFO, AVCO, or Standard Price)
   - Configure the required **Inventory Accounts**:
     - Inventory Account (Asset account for stock value)
     - Cost of Goods Sold Account (Expense account for sold items)
     - Stock Input Account (Liability account for received stock)
     - Price Difference Account (For valuation adjustments)

### 2. Set Up Stock Locations

1. Navigate to **Stock Locations** in the Inventory Management section
2. Create your warehouse structure:
   - **Main Warehouse** (Internal type)
   - **Sub-locations** within warehouses (set parent relationship)
   - **Vendor Locations** (Vendor type)
   - **Customer Locations** (Customer type)

### 3. Track Stock Movements

Stock movements are automatically created when:
- Vendor bills are posted (creates incoming movements)
- Invoices are posted (creates outgoing movements)

You can also create manual movements:
1. Navigate to **Stock Movements** in the Inventory Management section
2. Click **Create Stock Movement**
3. Fill in the movement details:
   - Select the product
   - Choose from/to locations
   - Enter quantity and movement type
   - Set the movement date

## Valuation Methods Explained

### FIFO (First In, First Out)
- Oldest inventory is consumed first
- Cost layers track each purchase separately
- Good for perishable goods or when costs are rising

### LIFO (Last In, First Out)
- Newest inventory is consumed first
- Cost layers track each purchase separately
- Useful when costs are falling

### AVCO (Average Cost)
- Weighted average cost calculation
- Single average cost maintained per product
- Simplest method for most businesses

### Standard Price
- Fixed cost per unit
- Variances tracked in price difference account
- Good for manufacturing with standard costs

## Best Practices

### 1. Account Configuration
- Set up dedicated inventory accounts for each product category
- Use separate COGS accounts for different product lines
- Configure price difference accounts for variance tracking

### 2. Location Structure
- Create logical warehouse hierarchies
- Use descriptive names for easy identification
- Keep vendor and customer locations separate

### 3. Movement Management
- Use reference numbers for better tracking
- Confirm movements promptly to maintain accuracy
- Review cost layers regularly for FIFO/LIFO products

### 4. Reporting and Analysis
- Monitor average costs for pricing decisions
- Review stock movement history for audit purposes
- Use cost layer information for detailed cost analysis

## Troubleshooting

### Common Issues

**Q: Why can't I see inventory fields on my product?**
A: Make sure the product type is set to "Storable Product". Inventory fields are only visible for storable products.

**Q: Why are my cost layers empty?**
A: Cost layers are only created for FIFO and LIFO valuation methods. AVCO products use a single average cost.

**Q: Can I edit a confirmed stock movement?**
A: No, only draft movements can be edited. This maintains data integrity and audit trails.

**Q: How do I adjust inventory for damaged goods?**
A: Create a stock movement with type "Adjustment" from your warehouse to an adjustment location.

## Multi-Language Support

The stock management system supports both English and Kurdish (Sorani) languages. All interface elements, labels, and messages are fully translated. Users can switch between languages using the language selector in the application header.

## Integration with Accounting

Stock movements automatically generate journal entries that:
- Update inventory asset accounts
- Record cost of goods sold
- Track stock input liabilities
- Handle price differences and adjustments

This ensures your inventory values are always synchronized with your general ledger.
