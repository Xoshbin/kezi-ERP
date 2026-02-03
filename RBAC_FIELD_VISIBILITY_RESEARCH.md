# Research Report: Scalable RBAC Field Visibility

## 1. Executive Summary
The current Kezi ERP relies on standard Model-based Authorization (Role -> Permission -> Model Action). It lacks a standardized **Field Level Security (FLS)** layer.

Implementing ad-hoc checks (e.g., `->visible(fn() => user()->can('update_product'))`) directly in Filament resources is **not scalable**. It leads to scattered logic, harder maintenance, and inconsistent behavior across the application.

This report analyzes how enterprise ERPs (Odoo, SAP) handle this, evaluates our current ecosystem, and proposes **three architectural strategies** to implement a robust, dynamic Field Visibility layer in Kezi.

---

## 2. Current Ecosystem Analysis

### Existing Structure
-   **RBAC**: Standard *Spatie Laravel Permission*.
-   **Seeder**: `RolesAndPermissionsSeeder.php` defines granular permissions per resource (e.g., `view_any_product`, `create_product`).
-   **Gap**: No structure exists to map a Roll/Permission to specific *columns* or *form fields*.

### Problem with Ad-Hoc Approach
Adding `->visible()` closures to every sensitive field creates:
1.  **High Maintenance**: Changing a rule requires finding every instance of that field across Forms, Tables, and Infolists.
2.  **Invisibility**: You cannot query "Who can see the `cost` field?" from a central place.
3.  **Inconsistency**: Different developers might use different permissions (e.g., `update_product` vs `view_cost_product`) for the same logic.

---

## 3. Comparative Research: Enterprise ERPs

### Odoo (Python)
Odoo uses a tiered security architecture:
1.  **Access Rights (Model Level)**: Can I read/write this table? (CSV defined).
2.  **Record Rules (Row Level)**: Can I see rows where `company_id = my_company`? (Domain filter).
3.  **Field Access (Column Level)**:
    -   **Field Groups**: `cost = fields.Float(groups="base.group_manager")`.
    -   **View Modifiers**: `<field name="cost" groups="!base.group_user"/>`.
    -   **Takeaway**: Field permissions are often defined **declaratively** on the model or the view definition, linked to a central Group.

### SAP (ABAP)
SAP uses **Authorization Objects**:
1.  **Auth Object**: A container checking multiple fields (e.g., `M_MATE_STA`).
2.  **Field Level**: Access can be restricted by organizational levels (Plant, Company Code) and Activity (Display, Change).
3.  **Field Masking**: Advanced modules allow UI masking (e.g., `*****`) for sensitive fields like salaries based on attributes.
4.  **Takeaway**: Extremely granular, but high complexity setup (Transaction `SU21`).

---

## 4. Proposed Solutions for Kezi

We propose moving from *Imperative* (code logic) to *Declarative* (configuration) field security.

### Strategy A: The "Policy Method" Pattern (Low Complexity)
Standardize field access logic within the existing Policy classes, rather than the Resource.

**Implementation:**
Add a method `viewField(User $user, $model, string $field)` to base Policy.
```php
// ProductPolicy.php
public function viewField(User $user, Product $product, string $field) {
    if ($field === 'average_cost') {
        return $user->hasPermissionTo('view_cost_product') || $user->hasRole('Manager');
    }
    return true;
}
```
**Pros:** Uses standard Laravel Policies. Easy to understand.
**Cons:** Still requires hardcoded strings in Policy files.

### Strategy B: The "Field Permission Registry" (Medium Complexity - Recommended)
Create a centralized config or service that maps Fields to Permissions. This mimics Odoo's declarative approach.

**Implementation:**
1.  Define a registry (PHP Array or Config):
    ```php
    // config/field-permissions.php
    return [
        Modules\Product\Models\Product::class => [
            'average_cost' => 'view_financial_data',
            'margin' => 'view_financial_data',
        ],
        Modules\Accounting\Models\Invoice::class => [
             'total_amount' => 'view_invoice_totals',
        ]
    ];
    ```
2.  Create a custom Filament Plugin or Trait (`HasFieldProtection`) used in Resources using `getFormSchema()`.
3.  The Trait recursively iterates over the schema, identifies fields by name, and applies the `hidden()` logic based on the registry.

**Pros:**
-   **Centralized**: All sensitive field rules in one place.
-   **Dynamic**: Can be modified without touching Resource files.
-   **Scalable**: Applying a rule to 'view_financial_data' updates all related fields instantly.

### Strategy C: Database-Driven Field Security (High Complexity)
Store field permissions in the database (`field_permissions` table: `role_id`, `model`, `field`, `access_level`).

**Pros:** Allows Admin UI (Filament Shield style) to toggle visibility at runtime.
**Cons:** Performance impact (extra queries), high development effort, potential caching complexity.

---

## 5. Recommendation

**Adopt Strategy B (Field Permission Registry)**.

It offers the best balance of maintainability and performance. It solves the user's request for a "broader way" to manage permissions by decoupling the visibility logic from the UI definition.

**Next Steps:**
1.  Define the `FieldPermissionService`.
2.  Create a `SecureField` wrapper or Trait for Filament Resources.
3.  Refactor `ProductResource` to use this system instead of manual closures.
