# Understanding Numbering Settings

Numbering Settings control how Kezi automatically generates reference numbers for invoices, bills, payments, and other documents.

## Overview

Every financial document in Kezi needs a unique reference number. Numbering Settings let you define the format and sequence for each document type, ensuring consistent and professional document identification.

## Key Concepts

### Prefix

Characters that appear before the number. Common uses:
*   **Document type**: INV (Invoice), BILL (Bill), PAY (Payment)
*   **Year/Month**: 2024- or 2024/01/
*   **Location**: HQ-, BR1-

### Sequence Number

The incrementing number portion. You can set:
*   **Starting number**: What number to begin with
*   **Padding**: How many digits (e.g., 0001 vs 1)
*   **Increment**: How much to add for each new document

### Suffix

Optional characters after the number. Less common but useful for:
*   **Version indicators**: -A, -REV
*   **Department codes**: -FIN, -OPS

## Configuring Numbering

1.  Navigate to **Settings > Numbering Settings**.
2.  Find the document type you want to configure.
3.  Set the format:
    *   **Prefix**: Text before the number
    *   **Next Number**: Current sequence position
    *   **Padding**: Minimum digits (zeros added)
    *   **Suffix**: Text after the number (optional)

## Document Types

Numbering is configured for each document type:

| Document Type | Example Format | Sample Result |
|---------------|----------------|---------------|
| Customer Invoice | INV-{YYYY}- | INV-2024-0001 |
| Vendor Bill | BILL- | BILL-0001 |
| Payment | PAY-{YYMM}- | PAY-2401-0001 |
| Journal Entry | JE- | JE-0001 |
| Credit Note | CN- | CN-0001 |

## Reset Options

Numbers can be reset based on:
*   **Never**: Continuous counting
*   **Yearly**: Resets on fiscal year start
*   **Monthly**: Resets each month

## Best Practices

1.  **Plan Before Starting**: Decide on format before creating documents
2.  **Keep It Simple**: Shorter is usually better
3.  **Be Consistent**: Use same patterns across document types
4.  **Don't Change Mid-Year**: Avoid format changes during fiscal year
5.  **Test First**: Create a test document to verify format

## Related Topics

*   [Customer Invoices](customer-invoices.md)
*   [Vendor Bills](vendor-bills.md)
*   [Journal Entries](journal-entries.md)
