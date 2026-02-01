<?php

namespace Jmeryar\Product\Observers;

use Jmeryar\Product\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleting" event.
     */
    public function deleting(Product $product): void
    {
        // Check if the product is used in any financial transactions.
        if ($product->invoiceLines()->exists() || $product->vendorBillLines()->exists()) {
            // If it is, throw a specific exception to prevent the deletion.
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete a product that has been used in transactions.'
            );
        }

        // Prevent deletion of template if variants exist
        if ($product->is_template && $product->variants()->exists()) {
            $variantCount = $product->variants()->count();
            throw new \RuntimeException(
                "Cannot delete template product with {$variantCount} existing variant(s). Delete variants first or deactivate the template."
            );
        }
    }

    /**
     * Handle the Product "updating" event.
     */
    public function updating(Product $product): void
    {
        if ($product->is_template
            && $product->variants()->exists()
            && $product->isDirty(['product_attributes', 'productAttributes', 'sku', 'type'])) {

            throw new \RuntimeException(
                'Cannot modify attributes or critical fields of a template that has existing variants. '.
                'Delete variants first, or create a new template.'
            );
        }

        // If it's a variant and unit_price is being changed, mark as overridden
        if ($product->parent_product_id && ! $product->is_template && $product->isDirty('unit_price')) {
            $product->has_price_override = true;
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // If template is updated, sync common fields to variants using mass update to bypass observers on variants
        if ($product->is_template) {
            $syncFields = [
                'name', 'description', 'income_account_id', 'expense_account_id',
                'type', 'inventory_valuation_method', 'default_inventory_account_id',
                'default_cogs_account_id', 'default_stock_input_account_id',
                'default_price_difference_account_id', 'tracking_type',
                'weight', 'volume', 'is_active',
            ];

            $updates = [];
            foreach ($syncFields as $field) {
                if ($product->isDirty($field)) {
                    $updates[$field] = $product->getAttributes()[$field] ?? null;
                }
            }

            if (! empty($updates)) {
                $product->variants()->update($updates);
            }

            // Sync unit_price only to variants that don't have an override
            if ($product->isDirty('unit_price')) {
                $product->variants()->where('has_price_override', false)->update([
                    'unit_price' => $product->getAttributes()['unit_price'] ?? null,
                ]);
            }
        }
    }
}
