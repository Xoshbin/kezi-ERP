# Product Variants - Production Readiness Roadmap

**Date Created:** 2026-01-20  
**Status:** Foundation Complete - Integration Required  
**Priority:** HIGH

---

## Executive Summary

The Product Variants feature has been successfully implemented with core functionality working correctly. However, **it is NOT production-ready** due to missing critical integrations with the Inventory and Accounting modules. This document outlines the remaining work required to make Product Variants fully operational in the JMeryar ERP system.

---

## Current State

### ✅ Completed (Foundation)

1. **Database Schema**
   - `product_attributes` table with company scoping
   - `product_attribute_values` table
   - `product_variant_attributes` junction table
   - `products` table extended with `is_template`, `parent_product_id`, `variant_sku_suffix`

2. **Core Logic**
   - `GenerateProductVariantsAction` - Cartesian product generation
   - `GenerateProductVariantsDTO` - Data transfer object
   - Proper Service-Action-DTO pattern
   - Database transactions for atomicity

3. **UI/UX**
   - `ProductAttributeResource` - Manage attributes and values
   - Product form with template toggle
   - Attribute selection repeater for templates
   - "Generate Variants" action in EditProduct page
   - `VariantsRelationManager` - View generated variants
   - Full translations (EN/CKB)
   - Documentation files

4. **Testing**
   - Unit tests: `ProductVariantTest.php` (2 tests passing)
   - Integration tests: `ProductResourceTest.php` (1 test passing)
   - Multi-tenancy properly configured

---

## Critical Gaps (MUST FIX)

### 1. Inventory Integration ⚠️ CRITICAL

**Problem:** Variants are separate products but inventory system doesn't properly handle them.

**Required Work:**

#### 1.1 Stock Tracking per Variant
- [ ] Verify `StockQuant` works correctly with variant products
- [ ] Test stock moves with variant products
- [ ] Ensure lot tracking works per variant (if enabled)
- [ ] Test stock reservations for variants

**Files to Modify:**
- `Modules/Inventory/app/Services/StockMoveService.php`
- `Modules/Inventory/app/Observers/StockMoveObserver.php`

**Tests to Create:**
```php
// Modules/Inventory/tests/Feature/ProductVariantInventoryTest.php
- test_can_create_stock_move_for_variant()
- test_variant_has_independent_stock_levels()
- test_template_cannot_have_stock_moves()
- test_stock_quant_tracks_variant_separately()
- test_lot_tracking_works_per_variant()
```

#### 1.2 Template Restrictions
- [ ] Prevent stock moves for template products
- [ ] Add validation in `StockMoveService`
- [ ] Add observer to block template stock operations

**Implementation:**
```php
// In StockMoveService or Observer
if ($product->is_template) {
    throw new \InvalidArgumentException(
        'Cannot create stock moves for template products. Use variants instead.'
    );
}
```

#### 1.3 Reordering Rules
- [ ] Ensure reordering rules work per variant
- [ ] Test minimum stock levels per variant
- [ ] Verify replenishment suggestions for variants

---

### 2. Accounting Integration ⚠️ CRITICAL

**Problem:** Variants inherit accounts from templates but no validation or testing exists.

**Required Work:**

#### 2.1 Sales Integration
- [ ] Test creating invoice lines with variant products
- [ ] Verify correct income account is used
- [ ] Test revenue recognition per variant
- [ ] Ensure pricing works correctly per variant

**Files to Modify:**
- `Modules/Sales/app/Services/InvoiceService.php`
- `Modules/Sales/app/Actions/CreateInvoiceLineAction.php`

**Tests to Create:**
```php
// Modules/Sales/tests/Feature/VariantSalesTest.php
- test_can_create_invoice_with_variant_product()
- test_variant_uses_correct_income_account()
- test_variant_pricing_on_invoice()
- test_variant_revenue_recognition()
```

#### 2.2 Purchase Integration
- [ ] Test creating vendor bill lines with variant products
- [ ] Verify correct expense account is used
- [ ] Test COGS calculation per variant
- [ ] Ensure cost layers work per variant

**Files to Modify:**
- `Modules/Purchase/app/Services/VendorBillService.php`
- `Modules/Purchase/app/Actions/CreateVendorBillLineAction.php`

**Tests to Create:**
```php
// Modules/Purchase/tests/Feature/VariantPurchaseTest.php
- test_can_create_vendor_bill_with_variant_product()
- test_variant_uses_correct_expense_account()
- test_variant_cost_layer_creation()
- test_variant_average_cost_calculation()
```

#### 2.3 Cost Layer Management
- [ ] Verify `InventoryCostLayer` works per variant
- [ ] Test FIFO/LIFO/AVCO per variant
- [ ] Ensure cost layers don't mix between variants

**Tests to Create:**
```php
// Modules/Inventory/tests/Feature/VariantCostLayerTest.php
- test_variant_has_independent_cost_layers()
- test_fifo_calculation_per_variant()
- test_average_cost_per_variant()
```

---

## High Priority Gaps

### 3. Business Logic Protection ⚠️ HIGH

#### 3.1 Template Deletion Prevention
- [ ] Prevent deletion of template if variants exist
- [ ] Add soft delete check in `ProductObserver`
- [ ] Show error message with variant count

**Implementation:**
```php
// In ProductObserver::deleting()
if ($product->is_template && $product->variants()->exists()) {
    throw new \RuntimeException(
        "Cannot delete template product with existing variants. Delete variants first."
    );
}
```

#### 3.2 Template Update Handling
- [ ] Define behavior when template attributes change
- [ ] Options:
  - Prevent changes if variants exist
  - Update all variants automatically
  - Mark variants as "out of sync"
- [ ] Implement chosen strategy

#### 3.3 Variant Regeneration
- [ ] Handle duplicate SKUs if regenerating
- [ ] Options:
  - Delete existing variants first
  - Skip duplicates
  - Append version number
- [ ] Add confirmation dialog

---

## Medium Priority Gaps

### 4. Enhanced User Experience ⚠️ MEDIUM

#### 4.1 Success Notifications
- [ ] Add success notification after variant generation
- [ ] Show count of variants created
- [ ] Provide link to view variants

**Implementation:**
```php
Notification::make()
    ->title('Variants Generated Successfully')
    ->body("{$variants->count()} variants created")
    ->success()
    ->send();
```

#### 4.2 Variant Preview
- [ ] Add preview modal before generation
- [ ] Show SKUs that will be created
- [ ] Allow deselection of specific combinations

#### 4.3 Bulk Variant Operations
- [ ] Bulk price update for variants
- [ ] Bulk attribute update
- [ ] Bulk activation/deactivation

#### 4.4 Variant Comparison View
- [ ] Table comparing all variants
- [ ] Show differences in attributes
- [ ] Quick edit capabilities

---

## Low Priority Gaps

### 5. Edge Cases & Validation ⚠️ LOW

#### 5.1 SKU Uniqueness
- [ ] Validate SKU uniqueness before variant creation
- [ ] Handle conflicts gracefully
- [ ] Suggest alternative SKUs

#### 5.2 Variant Limits
- [ ] Add maximum variant count validation
- [ ] Prevent excessive combinations (e.g., > 1000)
- [ ] Show warning for large combinations

#### 5.3 Attribute Value Deletion
- [ ] Prevent deletion of values used in variants
- [ ] Or cascade delete/update variants
- [ ] Add confirmation dialog

#### 5.4 Variant-Specific Pricing
- [ ] Allow price overrides per variant
- [ ] Price rules based on attributes
- [ ] Bulk pricing strategies

---

## Implementation Phases

### Phase 1: Critical Integrations (REQUIRED FOR PRODUCTION)
**Estimated Effort:** 3-5 days  
**Priority:** CRITICAL

1. Inventory integration (1.1 - 1.3)
2. Accounting integration (2.1 - 2.3)
3. Comprehensive testing

**Deliverables:**
- All inventory tests passing
- All accounting tests passing
- Variants can be sold and purchased
- Stock tracking works per variant

---

### Phase 2: Business Logic Protection (RECOMMENDED)
**Estimated Effort:** 1-2 days  
**Priority:** HIGH

1. Template deletion prevention (3.1)
2. Template update handling (3.2)
3. Variant regeneration logic (3.3)

**Deliverables:**
- Safe template management
- Clear error messages
- No data corruption risks

---

### Phase 3: Enhanced UX (OPTIONAL)
**Estimated Effort:** 2-3 days  
**Priority:** MEDIUM

1. Success notifications (4.1)
2. Variant preview (4.2)
3. Bulk operations (4.3)
4. Comparison view (4.4)

**Deliverables:**
- Improved user experience
- Faster variant management
- Better visibility

---

### Phase 4: Edge Cases (OPTIONAL)
**Estimated Effort:** 1-2 days  
**Priority:** LOW

1. SKU validation (5.1)
2. Variant limits (5.2)
3. Attribute deletion handling (5.3)
4. Variant-specific pricing (5.4)

**Deliverables:**
- Robust error handling
- Better data integrity
- Advanced features

---

## Testing Strategy

### Required Test Coverage

1. **Unit Tests**
   - ✅ Variant generation logic (DONE)
   - ✅ DTO validation (DONE)
   - [ ] Template validation
   - [ ] SKU generation edge cases

2. **Integration Tests**
   - ✅ Filament action (DONE)
   - [ ] Inventory movements with variants
   - [ ] Invoice creation with variants
   - [ ] Vendor bill creation with variants
   - [ ] Cost layer calculations

3. **Feature Tests**
   - [ ] End-to-end variant lifecycle
   - [ ] Multi-variant sales order
   - [ ] Multi-variant purchase order
   - [ ] Stock transfer between variants

---

## Files Reference

### Core Implementation
- `Modules/Product/app/Actions/GenerateProductVariantsAction.php`
- `Modules/Product/app/DataTransferObjects/GenerateProductVariantsDTO.php`
- `Modules/Product/app/Models/Product.php`
- `Modules/Product/app/Models/ProductAttribute.php`
- `Modules/Product/app/Models/ProductAttributeValue.php`
- `Modules/Product/app/Models/ProductVariantAttribute.php`

### UI Components
- `Modules/Inventory/app/Filament/Clusters/Inventory/Resources/Products/ProductResource.php`
- `Modules/Inventory/app/Filament/Clusters/Inventory/Resources/Products/Pages/EditProduct.php`
- `Modules/Inventory/app/Filament/Clusters/Inventory/Resources/Products/RelationManagers/VariantsRelationManager.php`
- `Modules/Product/app/Filament/Resources/ProductAttributeResource.php`

### Migrations
- `Modules/Product/database/migrations/2026_01_20_192052_create_product_attributes_table.php`
- `Modules/Product/database/migrations/2026_01_20_192053_create_product_attribute_values_table.php`
- `Modules/Product/database/migrations/2026_01_20_192053_add_variant_fields_to_products_table.php`
- `Modules/Product/database/migrations/2026_01_20_192053_create_product_variant_attributes_table.php`

### Tests
- `Modules/Product/tests/Feature/ProductVariantTest.php`
- `Modules/Inventory/tests/Feature/ProductResourceTest.php`

---

## Next LLM Session Prompt

```
I need to complete the Product Variants feature for production deployment. The foundation is complete but critical integrations are missing.

PRIORITY 1 (CRITICAL): Inventory Integration
- Implement stock tracking per variant
- Prevent stock moves for template products
- Test all inventory operations with variants
- Create ProductVariantInventoryTest.php with comprehensive coverage

PRIORITY 2 (CRITICAL): Accounting Integration
- Test invoice creation with variant products
- Test vendor bill creation with variant products
- Verify cost layer calculations per variant
- Create VariantSalesTest.php and VariantPurchaseTest.php

PRIORITY 3 (HIGH): Business Logic Protection
- Prevent template deletion if variants exist
- Handle template attribute updates
- Implement variant regeneration logic

Please start with PRIORITY 1 and create comprehensive tests before implementing any logic changes. Follow the Service-Action-DTO pattern and ensure all tests pass before moving to the next priority.

Reference: PRODUCT_VARIANTS_ROADMAP.md
```

---

## Success Criteria

The Product Variants feature will be considered production-ready when:

- [ ] All Phase 1 tasks completed
- [ ] All inventory tests passing (minimum 10 tests)
- [ ] All accounting tests passing (minimum 10 tests)
- [ ] Can create sales order with variant products
- [ ] Can create purchase order with variant products
- [ ] Stock levels tracked independently per variant
- [ ] Cost layers calculated correctly per variant
- [ ] Template deletion properly restricted
- [ ] No PHPStan errors
- [ ] Full test suite passing (`php artisan test --parallel`)

---

## Notes

- The current implementation follows Odoo's variant model closely
- SKU generation uses template SKU + attribute value names
- Variants inherit all properties from template except attributes
- Multi-tenancy is properly implemented and tested
- All UI strings are translated (EN/CKB)

---

**Last Updated:** 2026-01-20  
**Next Review:** After Phase 1 completion
