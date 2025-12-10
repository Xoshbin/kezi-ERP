---
title: Stock Management
icon: heroicon-o-archive-box
order: 11
---


This comprehensive guide explains how stock management works in your accounting system, covering inventory configuration, valuation methods, warehouse locations, and movement tracking. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is Stock Management?

Stock management is the systematic tracking and valuation of inventory throughout your business operations, providing accurate cost information for financial reporting and operational decision-making.

- **Inventory Tracking**: Monitor stock quantities and locations in real-time
- **Cost Valuation**: Calculate inventory costs using FIFO, LIFO, AVCO, or Standard Price methods
- **Movement Recording**: Document all stock movements with complete audit trails
- **Accounting Integration**: Automatic journal entries for inventory transactions

**Accounting Purpose**: Stock management ensures accurate inventory valuation for balance sheet reporting and proper cost of goods sold calculation for income statement accuracy.

---

## System Requirements

### Product Configuration
- **Storable Products**: Products must be configured as "Storable Product" type for inventory tracking
- **Valuation Methods**: FIFO, LIFO, AVCO (Average Cost), or Standard Price methods must be selected
- **Account Setup**: Inventory, COGS, and stock input accounts must be configured
- **Location Structure**: Warehouse locations must be created and organized

### Prerequisites
1. **Chart of Accounts**: Inventory asset, COGS expense, and stock input liability accounts configured
2. **Product Catalog**: Products set up with appropriate inventory configuration
3. **Location Hierarchy**: Warehouse and location structure established
4. **User Permissions**: Access to inventory management and accounting features

---

## Where to find it in the UI

Navigate to **Inventory → Products** or **Inventory → Stock Locations**

Stock management also appears in:
- **Stock Movements**: View and create inventory movements
- **Vendor Bills**: Automatic stock movements when posting bills for storable products
- **Customer Invoices**: Automatic stock movements when posting invoices for storable products
- **Reports**: Inventory valuation and movement reports

**Tip**: The header's Help/Docs button opens this guide.

---

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
