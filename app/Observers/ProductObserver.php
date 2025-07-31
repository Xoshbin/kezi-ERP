<?php

namespace App\Observers;

use App\Models\Product;
use App\Exceptions\DeletionNotAllowedException;

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
            throw new DeletionNotAllowedException(
                'Cannot delete a product that has been used in transactions.'
            );
        }
    }
}
