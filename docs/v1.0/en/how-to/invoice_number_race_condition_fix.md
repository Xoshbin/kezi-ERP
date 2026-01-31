# Invoice Number Race Condition Fix

## Problem Description

The application was experiencing a database constraint violation error when posting multiple invoices:

```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1-1-INV-00004' 
for key 'journal_entries.journal_entries_company_id_journal_id_reference_unique'
```

This error occurred because the original invoice number generation logic had a race condition that could cause multiple invoices to receive the same invoice number when posted simultaneously or in quick succession.

## Root Cause

The issue was in the `getNextInvoiceNumber` method in `InvoiceService.php`:

```php
private function getNextInvoiceNumber(Company $company): string
{
    // Simple (but not race-condition-proof) way to get the next number.
    $lastNumber = Invoice::where('company_id', $company->id)
        ->whereNotNull('invoice_number')
        ->count();

    // Format it nicely, e.g., INV-00001
    return 'INV-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
}
```

**The Problem**: When multiple invoices were posted concurrently:
1. Both processes would call `count()` and get the same result (e.g., 3)
2. Both would generate the same invoice number (e.g., INV-00004)
3. Both would try to create journal entries with the same reference
4. The database unique constraint would be violated on the second insert

## Solution

We implemented a robust, atomic sequence generation system following accounting best practices:

### 1. Created a `sequences` Table

```sql
CREATE TABLE sequences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(255) NOT NULL,
    prefix VARCHAR(255) NOT NULL,
    current_number INT UNSIGNED NOT NULL DEFAULT 0,
    padding INT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY sequences_company_id_document_type_unique (company_id, document_type),
    INDEX sequences_company_id_document_type_index (company_id, document_type)
);
```

### 2. Created a `Sequence` Model

The model provides atomic number generation using database-level operations:

```php
public function getNextNumber(): string
{
    // Use atomic increment to prevent race conditions
    $this->increment('current_number');
    
    // Reload to get the updated value
    $this->refresh();
    
    // Format the number with prefix and padding
    return $this->prefix . '-' . str_pad($this->current_number, $this->padding, '0', STR_PAD_LEFT);
}
```

### 3. Created a `SequenceService`

This service provides convenient methods for different document types:

```php
public function getNextInvoiceNumber(Company $company): string
{
    $sequence = Sequence::getOrCreateSequence(
        companyId: $company->id,
        documentType: 'invoice',
        prefix: 'INV',
        padding: 5
    );

    return $sequence->getNextNumber();
}
```

### 4. Updated `InvoiceService`

Replaced the race-condition-prone method with the atomic sequence service:

```php
// OLD (race condition prone):
$invoice->invoice_number = $this->getNextInvoiceNumber($invoice->company);

// NEW (atomic and safe):
$invoice->invoice_number = $this->sequenceService->getNextInvoiceNumber($invoice->company);
```

## Key Benefits

1. **Atomic Operations**: Uses database-level atomic increment operations
2. **Race Condition Free**: Multiple concurrent requests cannot generate duplicate numbers
3. **Company Scoped**: Each company has its own sequence counters
4. **Document Type Specific**: Different document types (invoices, bills, payments) have separate sequences
5. **Accounting Compliant**: Follows accounting best practices for sequential numbering
6. **Extensible**: Easy to add new document types
7. **Auditable**: Maintains proper audit trails and immutability principles

## Architecture

The solution follows a layered architecture:

```
Web Interface (Filament)
    ↓
InvoiceService
    ↓
SequenceService
    ↓
Sequence Model
    ↓
Database (Atomic Operations)
```

## Testing

Comprehensive tests were added to verify the fix:

1. **Race Condition Tests**: Verify that concurrent number generation produces unique results
2. **Integration Tests**: Test the complete invoice posting workflow
3. **Web Interface Tests**: Test through the actual Filament interface
4. **Sequence Service Tests**: Test all sequence service functionality

## Migration

The fix is backward compatible and requires no data migration. Existing invoices keep their current numbers, and new invoices will use the atomic sequence system starting from the next available number.

## Future Enhancements

This sequence system can be extended to support:

- Vendor bill numbering
- Payment numbering
- Credit note numbering
- Journal entry numbering
- Custom document types

The system is designed to be flexible and maintainable while ensuring data integrity and accounting compliance.
