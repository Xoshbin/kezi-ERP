# Stock Management User Guide

## Overview

The stock management system provides comprehensive inventory tracking and valuation capabilities for your accounting application. It supports multiple valuation methods, hierarchical warehouse structures, and complete audit trails for all stock movements.

## Key Features

### 📦 **Product Inventory Configuration**
- **Valuation Methods**: FIFO, LIFO, AVCO (Average Cost), and Standard Price
- **Inventory Accounts**: Automatic configuration of inventory asset, COGS, stock input accounts
- **Average Cost Tracking**: Real-time calculation and display for AVCO products
- **Stock Movement History**: Complete audit trail of all product movements
- **Cost Layer Visibility**: View FIFO/LIFO cost layers for detailed cost tracking
- **Journal Entry Integration**: Automatic accounting entries for all inventory transactions

### 🏢 **Stock Location Management**
- **Hierarchical Structure**: Create parent-child relationships between locations
- **Location Types**: Internal warehouses, customer locations, vendor locations, and inventory adjustment locations
- **Multi-Company Support**: Company-specific location management
- **Active/Inactive Status**: Control which locations are available for use

### 📋 **Stock Movement Tracking**
- **Movement Types**: Incoming, Outgoing, Internal Transfer, and Adjustment
- **Status Workflow**: Draft → Confirmed → Done (with cancellation support)
- **Source Document Integration**: Automatic creation from vendor bills and customer invoices
- **Reference Numbers**: Optional reference tracking for better organization
- **Date-based Filtering**: Find movements by date ranges
- **Lock Date Enforcement**: Prevents modifications to closed periods

## Getting Started

### 1. Configure Products for Inventory

1. Navigate to **Products** in the **Inventory** section
2. Create or edit a product
3. Set the **Type** to "Storable Product"
4. In the **Inventory Management** section:
   - Choose a **Valuation Method** (FIFO, LIFO, AVCO, or Standard Price)
   - Configure the required **Inventory Accounts**:
     - Inventory Account (Asset account for stock value)
     - Cost of Goods Sold Account (Expense account for sold items)
     - Stock Input Account (Liability account for received stock)

### 2. Set Up Stock Locations

1. Navigate to **Stock Locations** in the **Inventory** section
2. Create your warehouse structure:
   - **Main Warehouse** (Internal type)
   - **Sub-locations** within warehouses (set parent relationship)
   - **Vendor Locations** (Vendor type)
   - **Customer Locations** (Customer type)
   - **Adjustment Locations** (Inventory Adjustment type) for corrections

### 3. Track Stock Movements

Stock movements are automatically created when:
- Vendor bills are posted (creates incoming movements)
- Customer invoices are posted (creates outgoing movements)

You can also create manual movements:
1. Navigate to **Stock Movements** in the **Inventory** section
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

## Integration with Other Modules

The stock management system integrates seamlessly with other parts of the application:

- **[Customer Invoices](customer-invoices.md)**: Automatically create outgoing stock movements when invoices are posted
- **[Payments](payments.md)**: Payment processing affects the financial aspects of inventory transactions
- **[Bank Reconciliation](bank-reconciliation.md)**: Reconcile payments related to inventory purchases and sales

## Advanced Features

### Automatic Journal Entries
All inventory movements generate corresponding journal entries:
- Incoming stock: Debit Inventory, Credit Stock Input
- Outgoing stock: Debit COGS, Credit Inventory
- Inventory adjustments: Debit/Credit Inventory and corresponding adjustment accounts

### Lock Date Protection
The system enforces accounting lock dates to prevent unauthorized changes to historical inventory data.

### Cost Layer Management
For FIFO and LIFO products, the system maintains detailed cost layers showing:
- Purchase date and quantity
- Unit cost and total value
- Remaining quantity and value
- Source document reference

## Troubleshooting

### Common Issues

**Q: Why can't I see inventory fields on my product?**
A: Make sure the product type is set to "Storable Product". Inventory fields are only visible for storable products.

**Q: Why are my cost layers empty?**
A: Cost layers are only created for FIFO and LIFO valuation methods. AVCO products use a single average cost.

**Q: Can I edit a completed stock movement?**
A: No, only draft movements can be edited. Stock movements with "Done" status cannot be modified to maintain data integrity and audit trails.

**Q: What's the difference between movement statuses?**
A: Draft (editable) → Confirmed (locked) → Done (completed with journal entries)

**Q: How do I adjust inventory for damaged goods?**
A: Create a stock movement with type "Adjustment" from your warehouse to an adjustment location.

## Multi-Language Support

The stock management system supports English, Kurdish (Sorani), and Arabic languages. All interface elements, labels, and messages are fully translated. Users can switch between languages using the language selector in the application header.

## Integration with Accounting

Stock movements automatically generate journal entries that:
- Update inventory asset accounts
- Record cost of goods sold
- Track stock input liabilities
- Handle price differences and adjustments

This ensures your inventory values are always synchronized with your general ledger.
