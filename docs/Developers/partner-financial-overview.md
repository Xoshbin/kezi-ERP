# Partner Financial Overview Implementation

## Overview

This implementation adds comprehensive financial information to the Partner Resource in Filament, providing accounting professionals with critical insights into customer receivables and vendor payables directly from the partner list view.

## Features Implemented

### 1. Financial Balance Calculations

**Customer Balances (Accounts Receivable)**
- **Outstanding Balance**: Total unpaid amount from posted invoices
- **Overdue Balance**: Amount from invoices past their due date
- Color-coded badges: Green for receivables, Gray for zero amounts

**Vendor Balances (Accounts Payable)**  
- **Outstanding Balance**: Total unpaid amount from posted vendor bills
- **Overdue Balance**: Amount from bills past their due date
- Color-coded badges: Red for payables, Gray for zero amounts

### 2. Enhanced Partner Table Columns

**Financial Information**
- Customer Outstanding (Green badge)
- Customer Overdue (Warning badge)
- Vendor Outstanding (Red badge) 
- Vendor Overdue (Warning badge)
- Last Activity Date

**Conditional Display**
- Customer columns only show for Customer/Both partner types
- Vendor columns only show for Vendor/Both partner types
- Non-applicable columns show "-" instead of amounts

**Contact & Address Information**
- Made toggleable (hidden by default) to prioritize financial data
- Still searchable and accessible when needed

### 3. Advanced Filtering

**Financial Filters**
- Has Overdue Amounts: Partners with past-due invoices or bills
- Has Outstanding Balance: Partners with any unpaid amounts
- Partner Type: Customer, Vendor, or Both
- Active Status: Active/Inactive partners

**Smart Query Optimization**
- Filters use efficient database queries
- Proper joins with payment_document_links table
- Only considers posted/paid status documents

### 4. Partner Model Enhancements

**New Financial Methods**
```php
getCustomerOutstandingBalance(): Money
getVendorOutstandingBalance(): Money  
getCustomerOverdueBalance(): Money
getVendorOverdueBalance(): Money
getLastTransactionDate(): ?Carbon
hasOverdueAmounts(): bool
getTotalLifetimeValue(): Money
```

**Key Features**
- Proper Money object handling with currency support
- Efficient relationship loading
- Type-aware calculations (respects partner type)
- Uses existing HasPaymentState trait for accuracy

### 5. Multi-Language Support

**Translation Keys Added**
- English: customer_outstanding, customer_overdue, vendor_outstanding, vendor_overdue
- Kurdish: قەرزی کڕیار, قەرزی دواکەوتوو - کڕیار, قەرزی فرۆشیار, قەرزی دواکەوتوو - فرۆشیار
- Proper accounting terminology in both languages

## Technical Implementation

### Database Efficiency
- Uses existing payment state calculations from HasPaymentState trait
- Leverages payment_document_links table for accurate paid amounts
- Efficient queries that only load necessary data
- Proper eager loading to prevent N+1 queries

### Money Object Integration
- Full integration with Brick\Money for precise calculations
- Proper currency handling using company's base currency
- Formatted display using Money::formatTo() method
- Zero-amount detection for conditional styling

### Accounting Best Practices
- Only considers posted/paid status documents (ignores drafts)
- Separates workflow state from payment state
- Handles overpayment scenarios correctly
- Maintains audit trail integrity

## User Experience Design

### Visual Hierarchy
1. **Partner Name & Type** - Primary identification
2. **Financial Balances** - Critical business information
3. **Contact Information** - Secondary (toggleable)
4. **Administrative Data** - Tertiary (hidden by default)

### Color Coding System
- **Green**: Money owed to us (receivables) - positive cash flow
- **Red**: Money we owe (payables) - cash obligations  
- **Orange/Warning**: Overdue amounts - requires attention
- **Gray**: Zero amounts or non-applicable

### Responsive Design
- Most important columns visible by default
- Less critical information toggleable
- Proper column sizing and spacing
- Mobile-friendly layout considerations

## Testing Coverage

Comprehensive test suite covering:
- Customer balance calculations
- Vendor balance calculations  
- Overdue amount detection
- Multi-type partner handling
- Edge cases (zero balances, wrong types)
- Date calculations
- Currency handling

## Benefits for Accounting Users

### Cash Flow Management
- Immediate visibility of outstanding receivables
- Clear identification of overdue amounts
- Quick assessment of vendor payment obligations

### Credit Management  
- Easy identification of high-risk customers
- Overdue amount tracking for collections
- Partner activity monitoring

### Financial Planning
- Total outstanding amounts at a glance
- Aging information through overdue calculations
- Partner relationship value assessment

### Operational Efficiency
- Reduced need to drill down into individual records
- Quick filtering for specific financial conditions
- Streamlined partner management workflow

## Partner Financial Widgets

### Comprehensive Widget Dashboard

Added three specialized widget sets that provide detailed financial insights for each partner:

#### 1. Customer Financial Widget
**For Customer and Both partner types**
- **Total Outstanding**: Customer receivables with trend chart
- **Overdue Amount**: Past-due invoices requiring attention
- **Due Within 7 Days**: Immediate collection opportunities
- **Due Within 30 Days**: Upcoming collection pipeline
- **Average Payment Days**: Customer payment performance metric
- **Last Payment**: Most recent payment received with date

#### 2. Vendor Financial Widget
**For Vendor and Both partner types**
- **Total Payable**: Outstanding vendor obligations
- **Overdue Payable**: Past-due bills requiring payment
- **Pay Within 7 Days**: Urgent payment requirements
- **Pay Within 30 Days**: Upcoming payment obligations
- **Our Average Payment Days**: Our payment performance to vendor
- **Last Payment Made**: Most recent payment to vendor

#### 3. Partner Overview Widget
**For all partner types**
- **Lifetime Value**: Total business volume with yearly trend
- **This Month**: Current month transaction activity
- **Performance Score**: 0-100 rating based on payment behavior
- **Last Activity**: Most recent transaction date
- **Partner Type**: Customer, Vendor, or Both designation
- **Status**: Active/Inactive partner status

### Widget Features

**Smart Color Coding**
- Green: Receivables (money owed to us)
- Red: Payables (money we owe)
- Orange/Warning: Overdue amounts
- Blue/Info: Upcoming due dates
- Gray: Zero amounts or inactive

**Performance Metrics**
- Payment performance scoring algorithm
- Average payment days calculation
- Trend analysis with mini charts
- Activity monitoring and alerts

**Responsive Design**
- 3-column layout for optimal viewing
- Conditional display based on partner type
- Proper spacing and visual hierarchy
- Mobile-friendly responsive design

### Widget Integration

**View Page**: Complete financial dashboard with all widgets
**Edit Page**: Financial context while editing partner details
**Navigation**: Easy access via View action in partner table

### Advanced Analytics

**Due Date Analysis**
- 7-day and 30-day payment forecasting
- Overdue amount identification
- Cash flow planning support

**Payment Performance**
- Average payment days tracking
- Performance score calculation
- Payment history analysis

**Business Intelligence**
- Monthly transaction volume
- Lifetime value calculation
- Activity pattern recognition
- Risk assessment indicators

## Future Enhancements

Potential additions for enhanced functionality:
- Aging buckets (30/60/90 days overdue)
- Credit limit vs. exposure tracking
- Payment terms compliance indicators
- Partner risk scoring
- Export functionality for aging reports
- Dashboard widgets for top debtors/creditors
- Real-time payment notifications
- Automated collection workflows
