<?php

namespace Modules\Product\Observers;

use Modules\Product\Models\Product;

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
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException(
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
    }
}
