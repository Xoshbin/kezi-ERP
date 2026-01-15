# Understanding Sales Orders

Sales Orders are formal requests from customers to purchase products or services. They are a key step between quotes and invoices in the sales process.

## Overview

A Sales Order confirms a customer's intent to buy. It locks in pricing, specifies delivery terms, and serves as authorization to fulfill the order. Once fulfilled, Sales Orders are converted to invoices.

## Sales Order Workflow

```
Draft → Confirmed → Delivered → Invoiced
  └── Cancelled
```

1.  **Draft**: Create and edit the order
2.  **Confirmed**: Customer commits to the purchase
3.  **Delivered**: Products shipped or services rendered
4.  **Invoiced**: Invoice generated for payment

## Key Information

### Order Header

*   **Customer**: Who is buying
*   **Order Date**: When the order was placed
*   **Delivery Date**: Expected fulfillment date
*   **Reference**: Customer's PO number or your reference
*   **Salesperson**: Assigned sales representative
*   **Payment Terms**: When payment is expected

### Order Lines

Each line represents what's being sold:
*   **Product**: Item or service being sold
*   **Quantity**: How many units
*   **Unit Price**: Price per unit
*   **Discount**: Any discounts applied
*   **Tax**: Applicable taxes
*   **Subtotal**: Line total before tax

### Delivery Information

*   **Shipping Address**: Where to deliver
*   **Shipping Method**: How it will be sent
*   **Incoterms**: International trade terms
*   **Partial Delivery**: Allow split shipments?

## Creating a Sales Order

1.  Navigate to **Sales > Sales Orders**.
2.  Click **Create Sales Order**.
3.  Select the customer.
4.  Add products and quantities.
5.  Set pricing and discounts.
6.  Specify delivery requirements.
7.  Click **Confirm Order**.

## From Quote to Order

If you have an approved sales quote:

1.  Open the quote.
2.  Click **Convert to Sales Order**.
3.  Review and adjust if needed.
4.  Confirm the order.

## Fulfillment Process

After confirmation:

1.  **Check Inventory**: Verify stock availability
2.  **Create Picking**: Generate warehouse picking order
3.  **Ship Products**: Deliver to customer
4.  **Confirm Delivery**: Mark as delivered
5.  **Generate Invoice**: Create invoice for payment

## Invoicing Options

*   **Invoice on Order**: Bill immediately upon confirmation
*   **Invoice on Delivery**: Bill after shipping
*   **Milestone Billing**: Bill at project milestones
*   **Prepayment**: Require payment before fulfillment

## Best Practices

1.  **Verify Stock**: Check availability before confirming
2.  **Confirm Terms**: Ensure customer agrees to payment terms
3.  **Document Changes**: Track any modifications
4.  **Follow Up**: Monitor delivery status
5.  **Close Loop**: Ensure invoicing is complete

## Related Topics

*   [Understanding Sales Quotes](understanding-sales-quotes.md)
*   [Customer Invoices](customer-invoices.md)
*   [Inventory Management](inventory-management.md)
