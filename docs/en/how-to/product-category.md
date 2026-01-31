---
title: Product Categories
icon: heroicon-o-rectangle-stack
order: 10
---

# Product Categories

Manage hierarchical categories for your products.

This guide explains how to organize your products using the Product Category hierarchy system, allowing for structured organization and reporting.

---

## What are Product Categories?

Product Categories enable you to:
- Organize products into logical groups (e.g., Electronics > Computers > Laptops).
- Manage hierarchical relationships (Parent/Child).
- Ensure data integrity with built-in validation preventing circular dependencies.

---

## Where to find it in the UI

Navigate to **Products -> Product Categories** in the main navigation.

---

## Managing Categories

### Creating a Category
1. Click **New Product Category**.
2. Enter the **Name** of the category.
3. (Optional) Select a **Parent Category** to nest this category under another.
4. Click **Create** or **Create & Create Another**.

### Hierarchy & Validation
The system includes robust validation to maintain hierarchy integrity:
- **Self-Reference**: You cannot set a category as its own parent.
- **Circular Dependency**: You cannot create a loop (e.g., A is parent of B, B cannot be parent of A). The system will prevent saving if a cycle is detected.
- **Deletion Protection**: You cannot delete a category that has children. You must first re-parent or delete the child categories.

### Viewing the Hierarchy
The list view displays the **Name** and **Parent Category**, allowing you to see the structure at a glance. You can filter and search by name or parent.
