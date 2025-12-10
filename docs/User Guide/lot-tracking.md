---
title: Lot Tracking
icon: heroicon-o-tag
order: 6
---


This comprehensive guide explains how to implement and manage lot tracking in your inventory system, covering lot creation, FEFO allocation, expiration management, and complete traceability. Written for all users — accountants and non‑accountants — it provides practical guidance for industries requiring detailed product traceability.

---

## What is Lot Tracking?

Lot tracking is a comprehensive system that assigns unique identifiers to batches of products, enabling complete traceability from supplier to customer while managing expiration dates and ensuring proper rotation of perishable goods.

- **Unique Identification**: Each lot has a unique code for complete traceability
- **Expiration Management**: Track expiration dates and automate FEFO allocation
- **Forward/Backward Traceability**: Track products from source to destination and vice versa
- **Compliance Support**: Meet regulatory requirements for product recalls and audits
- **Quality Control**: Associate quality data and certifications with specific lots
- **FEFO Automation**: First Expired, First Out allocation for optimal rotation

**Accounting Purpose**: Lot tracking provides the detailed audit trails required for regulatory compliance while ensuring accurate inventory valuation and cost tracking at the lot level.

---

## System Requirements

### Product Configuration
- **Lot Tracking Enabled**: Products must have lot tracking activated
- **Expiration Tracking**: Optional expiration date management
- **Lot Code Format**: Standardized lot code naming conventions
- **Quality Parameters**: Optional quality attributes per lot

### Prerequisites
1. **Product Setup**: Products configured for lot tracking
2. **Location Structure**: Warehouse locations for lot storage
3. **User Training**: Staff trained on lot handling procedures
4. **Compliance Framework**: Understanding of regulatory requirements

---

## Where to find it in the UI

Navigate to **Inventory → Lots**

Lot tracking also appears in:
- **Stock Movements**: Lot selection and allocation during movements
- **Product Details**: Lot history and current lot quantities
- **Stock Quantities**: Stock levels broken down by lot
- **Reports**: Lot traceability and expiration reports
- **Vendor Bills**: Lot assignment during receipt processing
- **Customer Invoices**: Lot consumption during delivery processing

**Tip**: The header's Help/Docs button opens this guide.

---

## Enabling Lot Tracking

### Product Configuration

Navigate to **Inventory → Products** → Select Product → **Edit**

**Step 1: Enable Lot Tracking**
- **Track Lots**: Check the enable checkbox
- **Lot Generation**: Choose manual or automatic lot code generation
- **Expiration Tracking**: Enable for perishable goods
- **Quality Tracking**: Enable for products requiring quality data

**Step 2: Lot Code Configuration**
- **Naming Convention**: Define lot code format (e.g., LOT-YYYY-MM-###)
- **Auto-generation**: Set up automatic lot code creation rules
- **Validation Rules**: Define lot code validation requirements

**Step 3: Expiration Settings**
- **Default Shelf Life**: Standard expiration period for new lots
- **Warning Period**: Days before expiration to trigger alerts
- **FEFO Priority**: Enable automatic FEFO allocation

---

## Creating and Managing Lots

### Manual Lot Creation

Navigate to **Inventory → Lots** → **Create Lot**

**Step 1: Basic Lot Information**
- **Lot Code**: Unique identifier (e.g., LOT-2024-001)
- **Product**: Associated product (must have lot tracking enabled)
- **Creation Date**: Date the lot was created or received
- **Expiration Date**: When the lot expires (if applicable)

**Step 2: Supplier Information**
- **Supplier**: Vendor who provided the lot
- **Supplier Lot Code**: Vendor's internal lot reference
- **Purchase Order**: Associated purchase order reference
- **Certificate Number**: Quality certificate reference

**Step 3: Quality Data**
- **Quality Grade**: Product quality classification
- **Test Results**: Laboratory test results
- **Certifications**: Regulatory certifications
- **Notes**: Additional quality or handling notes

### Automatic Lot Creation

Lots are automatically created during:

**Vendor Bill Processing**:
- **Receipt Movements**: New lots created for incoming stock
- **Lot Assignment**: Based on bill line details
- **Expiration Calculation**: Using product's default shelf life

**Production Orders**:
- **Manufactured Lots**: New lots for produced goods
- **Component Consumption**: Tracking which lots were used in production
- **Quality Inheritance**: Quality data from component lots

---

## FEFO (First Expired, First Out) Management

### Automatic FEFO Allocation

The system automatically suggests FEFO allocation for lot-tracked products:

**Allocation Logic**:
- **Expiration Priority**: Lots closest to expiration are consumed first
- **Quality Considerations**: Higher quality lots may override expiration priority
- **Customer Requirements**: Specific customer lot requirements
- **Minimum Shelf Life**: Ensure adequate remaining shelf life for customers

**Override Capabilities**:
- **Manual Selection**: Choose specific lots when needed
- **Quality Override**: Select higher quality lots over expiration priority
- **Customer Specific**: Allocate specific lots for customer requirements

### Expiration Monitoring

**Expiration Alerts**:
- **Warning Notifications**: Alerts before lots expire
- **Dashboard Widgets**: Visual indicators of expiring lots
- **Report Generation**: Regular expiration reports
- **Automatic Actions**: Optional automatic actions for expired lots

**Expiration Handling**:
- **Quarantine**: Move expired lots to quarantine locations
- **Disposal**: Process disposal of expired products
- **Return to Vendor**: Return expired products to suppliers
- **Retest**: Extend expiration dates after quality retesting

---

## Lot Movement Processing

### Incoming Movements with Lots

**Receipt Processing**:
1. **Select Product**: Choose lot-tracked product
2. **Lot Assignment**: Assign to existing lot or create new lot
3. **Quantity Allocation**: Specify quantity for each lot
4. **Expiration Date**: Set or calculate expiration date
5. **Quality Data**: Enter quality information

**Lot Splitting**:
- **Partial Receipts**: Split vendor lots into multiple internal lots
- **Quality Segregation**: Separate lots by quality grades
- **Location Distribution**: Distribute lots across multiple locations

### Outgoing Movements with Lots

**Delivery Processing**:
1. **Quantity Required**: Specify total quantity needed
2. **FEFO Suggestion**: System suggests optimal lot allocation
3. **Lot Selection**: Confirm or modify lot selection
4. **Partial Consumption**: Handle partial lot consumption
5. **Remaining Quantities**: Update remaining lot quantities

**Lot Consolidation**:
- **Multiple Lots**: Combine multiple lots for single shipment
- **Quality Matching**: Ensure consistent quality across lots
- **Expiration Alignment**: Group lots with similar expiration dates

---

## Lot Traceability

### Forward Traceability

Track where lot quantities went:

**Customer Deliveries**:
- **Delivery Records**: Which customers received specific lots
- **Quantity Tracking**: How much of each lot was delivered
- **Date Tracking**: When deliveries occurred
- **Invoice References**: Associated invoice numbers

**Internal Usage**:
- **Production Consumption**: Which lots were used in manufacturing
- **Transfer History**: Internal movements between locations
- **Adjustment Records**: Any quantity adjustments

### Backward Traceability

Track where lot quantities came from:

**Supplier Information**:
- **Original Supplier**: Who provided the lot
- **Receipt Details**: When and how much was received
- **Purchase Order**: Associated procurement documents
- **Quality Certificates**: Original quality documentation

**Production History**:
- **Component Lots**: Which lots were used to produce this lot
- **Production Date**: When the lot was manufactured
- **Process Parameters**: Manufacturing conditions and settings
- **Quality Control**: Production quality test results

### Complete Lot History

Navigate to **Inventory → Lots** → Select Lot → **View History**

**Movement Timeline**:
- **Chronological Order**: All movements in date order
- **Quantity Changes**: How quantities changed over time
- **Location History**: Where the lot has been stored
- **Document References**: Associated documents for each movement

---

## Lot Reporting

### Lot Traceability Reports

Navigate to **Inventory → Reports → Lot Traceability**

**Report Parameters**:
- **Lot Selection**: Specific lots or date ranges
- **Product Filter**: Specific products or categories
- **Direction**: Forward, backward, or complete traceability
- **Detail Level**: Summary or detailed movement history

**Report Contents**:
- **Lot Details**: Lot codes, expiration dates, quantities
- **Movement History**: Complete movement timeline
- **Document References**: Associated purchase orders, invoices, etc.
- **Quality Data**: Quality grades and test results

### Expiration Reports

Navigate to **Inventory → Reports → Lot Expiration**

**Expiration Analysis**:
- **Expiring Soon**: Lots approaching expiration
- **Expired Lots**: Lots past expiration date
- **Shelf Life Remaining**: Days until expiration
- **Value at Risk**: Financial value of expiring inventory

**Action Planning**:
- **Priority Lists**: Lots requiring immediate attention
- **Disposal Planning**: Lots requiring disposal
- **Customer Allocation**: Lots suitable for specific customers
- **Retest Candidates**: Lots eligible for shelf life extension

---

## Quality Management

### Quality Attributes

**Standard Attributes**:
- **Grade**: Quality classification (A, B, C, etc.)
- **Purity**: Percentage purity or concentration
- **Moisture Content**: Moisture percentage
- **pH Level**: Acidity/alkalinity measurement

**Custom Attributes**:
- **Industry-Specific**: Attributes specific to your industry
- **Customer Requirements**: Attributes required by specific customers
- **Regulatory Parameters**: Attributes required for compliance
- **Process Parameters**: Manufacturing or handling conditions

### Quality Certificates

**Certificate Management**:
- **Upload Documents**: Attach quality certificates to lots
- **Certificate Numbers**: Track certificate reference numbers
- **Validity Periods**: Monitor certificate expiration dates
- **Compliance Status**: Track regulatory compliance status

---

## Best Practices

### 1. Lot Code Management
- **Consistent Naming**: Use standardized lot code formats
- **Meaningful Codes**: Include date, product, or supplier information
- **Unique Identification**: Ensure lot codes are never duplicated
- **Barcode Integration**: Use barcodes for efficient lot scanning

### 2. Expiration Management
- **Regular Monitoring**: Check expiration dates regularly
- **FEFO Compliance**: Follow FEFO allocation consistently
- **Customer Communication**: Inform customers of expiration dates
- **Disposal Procedures**: Establish clear disposal procedures

### 3. Quality Control
- **Documentation**: Maintain complete quality documentation
- **Regular Testing**: Perform regular quality testing
- **Trend Analysis**: Monitor quality trends over time
- **Supplier Feedback**: Provide quality feedback to suppliers

### 4. Traceability Maintenance
- **Complete Records**: Maintain complete movement records
- **Document Retention**: Keep all supporting documents
- **Regular Audits**: Perform regular traceability audits
- **System Backups**: Ensure traceability data is backed up

---

## Troubleshooting

### Common Issues

**Q: Why can't I select a specific lot for a movement?**
A: Check that the lot exists in the source location with sufficient available quantity. Also verify that the lot hasn't expired if expiration checking is enabled.

**Q: Why is the system not suggesting FEFO allocation?**
A: Ensure that FEFO is enabled for the product and that lots have expiration dates. Check that there are multiple lots available with different expiration dates.

**Q: How do I handle partial lot consumption?**
A: The system automatically handles partial consumption by updating the remaining quantity in the lot. The lot remains active until fully consumed.

**Q: Can I change a lot's expiration date after creation?**
A: Yes, but this should be done carefully and with proper authorization. Document the reason for the change and any quality retesting performed.

---

## Related Documentation

- [Inventory Management](inventory-management.md) - Complete system overview
- [Stock Movements](stock-movements.md) - Movement processing with lots
- [Vendor Bills](vendor-bills.md) - Lot assignment during receipts
- [Customer Invoices](customer-invoices.md) - Lot consumption during deliveries

---

Lot tracking provides the detailed traceability and quality management capabilities essential for industries with regulatory requirements, quality standards, or customer traceability demands.
