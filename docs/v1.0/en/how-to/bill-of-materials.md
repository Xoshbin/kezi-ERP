---
title: Bill of Materials
icon: heroicon-o-list-bullet
order: 10
---

# Bill of Materials (BOM) Management

## Overview

A Bill of Materials (BOM) defines the components and quantities required to manufacture a finished product. The BOM module allows you to create, manage, and cost your manufacturing recipes.

## Key Concepts

### Bill of Materials
A BOM is a structured list that specifies:
- The finished product being manufactured
- All component products required
- Quantity of each component needed per unit of finished product
- Optional work centers for routing

### BOM Types
- **Normal BOM**: Regular manufacturing recipe. Sub-assemblies of this type must be produced via their own Manufacturing Orders before being used.
- **Phantom BOM**: Used for virtual sub-assemblies. When an MO is created, Phantom components are automatically "exploded" into their raw material constituents.
- **Kit BOM**: Used for products that are sold as a set. Similar to Phantoms, they explode into their base components during manufacturing or sales.

## Creating a Bill of Materials

### Step 1: Navigate to Manufacturing
1. Go to **Manufacturing** → **Bills of Materials**
2. Click **New Bill of Materials**

### Step 2: Define the Finished Product
1. **Product**: Select the finished product this BOM will produce
2. **BOM Code**: Enter a unique identifier (e.g., `BOM-WIDGET-001`)
3. **Quantity**: Specify how many units this BOM produces (typically 1.0)

### Step 3: Add Component Lines
For each component required:

1. Click **Add Component** in the Components section
2. **Component Product**: Select the raw material or sub-assembly
3. **Quantity**: Enter how many units are consumed per BOM quantity
4. **Unit of Measure**: Verify the UoM matches the component product

**Example:**
To manufacture 1 Widget, you need:
- 2 units of Steel Plate
- 4 units of Bolts
- 1 unit of Paint

### Step 4: Optional - Add Work Centers
If your manufacturing process requires specific equipment:

1. In the **Routing** section, add work centers
2. Specify the sequence of operations
3. Define time required at each work center

### Step 5: Activate the BOM
1. Review all component quantities
2. Click **Save**
3. The BOM is now ready to be used in Manufacturing Orders

## BOM Costing

### Viewing BOM Cost
The system automatically calculates the total cost of a BOM based on:
- Component product costs
- Quantities required
- Current inventory valuation

### Cost Breakdown
Navigate to a BOM and view:
- **Total Component Cost**: Sum of all raw materials
- **Per Unit Cost**: Total cost divided by BOM quantity
- **Cost by Component**: Detailed breakdown showing each component's contribution

## Using BOMs in Manufacturing

### Creating Manufacturing Orders from BOMs
1. Open the BOM record
2. Click **Create Manufacturing Order**
3. Specify:
   - Quantity to produce
   - Source location (where components are stored)
   - Destination location (where finished goods will be placed)
   - Planned start date

### BOM Versioning
When you need to update a BOM:
1. Create a new BOM with a new code (e.g., `BOM-WIDGET-002`)
2. Mark the old BOM as inactive
3. New Manufacturing Orders will use the latest active BOM

## Best Practices

### Component Accuracy
- Double-check all quantities before activating
- Use consistent units of measure
- Include all components, even small items

### BOM Organization
- Use clear, descriptive BOM codes
- Include product name in the code (e.g., `BOM-CHAIR-OAK-001`)
- Document any special instructions in the notes field

### Cost Management
- Regularly review BOM costs
- Update component costs when supplier prices change
- Use BOM costing to set product selling prices

### Multi-Level BOMs & Recursive Explosion
For complex products, the system supports multi-level BOM explosion up to 10 levels deep. 

**How it works:**
1. Create BOMs for sub-assemblies first.
2. When creating the final product BOM, use those sub-assemblies as components.
3. If a sub-assembly BOM is of type **Kit** or **Phantom**, the system automatically "explodes" it during Manufacturing Order creation, replacing it with its raw materials.
4. If a sub-assembly BOM is of type **Normal**, it remains as a single component that requires its own separate Manufacturing Order.

**Benefits:**
- **Recursive Math**: Quantities are multiplied accurately through all levels.
- **Cost Rollup**: The "Structure & Cost" tab provides a total hierarchical breakdown of costs.

## Common Scenarios

### Scenario 1: Simple Product Manufacturing
**Product**: Wooden Chair
**Components**:
- 4x Chair Legs (Wood)
- 1x Chair Seat (Wood)
- 1x Chair Back (Wood)
- 8x Wood Screws

### Scenario 2: Multi-Level Assembly
**Product**: Office Desk
**Components**:
- 1x Desktop (Sub-assembly with its own BOM)
- 4x Desk Legs (Sub-assembly with its own BOM)
- 1x Hardware Kit (BOM with screws, brackets)

### Scenario 3: Variable Quantity BOM
**Product**: Paint Mix (10 Liters)
**BOM Quantity**: 10
**Components**:
- 7 Liters Base Paint
- 2 Liters Pigment
- 1 Liter Thinner

This allows you to scale production: ordering 20 liters will automatically consume 14L base, 4L pigment, 2L thinner.

## Troubleshooting

### BOM Cannot Be Deleted
**Issue**: "This BOM is referenced by Manufacturing Orders"
**Solution**: BOMs with associated Manufacturing Orders cannot be deleted. Mark as inactive instead.

### Component Cost Shows Zero
**Issue**: BOM cost calculation shows $0 for a component
**Solution**: Ensure the component product has a cost set in the Product master data.

### Wrong Quantities Consumed
**Issue**: Manufacturing Order consumed incorrect quantities
**Solution**: 
1. Check the BOM quantities are correct
2. Verify the Manufacturing Order quantity
3. Quantities consumed = BOM component qty × MO quantity

## Related Topics
- [Manufacturing Orders](manufacturing-orders.md)
- [Inventory Management](inventory-management.md)
- [Product Management](product-management.md)
- [Work Centers](work-centers.md)
